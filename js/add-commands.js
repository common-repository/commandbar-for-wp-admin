(function () {

  function metaKeyToLabel(key) {
    // Split on '.'
    let label = key.split('.').reverse()[0];

    // remove snake_case
    label = label.replace(/_/g, ' ');
    // remove camelCase
    label = label.replaceAll(/([A-Z])/g, ' $1');
    label = label.trim();

    return label.toLowerCase();
  }

  async function getPosts(query) {
    const posts = await fetch(
      wpApiSettings.root + 'commandbar/v1/posts/' + encodeURIComponent(query), {
      credentials: 'include',
      headers: {
        'content-type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
      }
    });

    return await posts.json();
  }

  function debounce(func, timeout) {
    let state = {};

    return (...args) => {
      const stateKey = JSON.stringify(args);

      if (!state[stateKey]) {
        state[stateKey] = {
          timer: null,
          result: null,
          waiters: []
        };
      }

      clearTimeout(state[stateKey].timer);
      state[stateKey].timer = setTimeout(() => {
        try {
          state[stateKey].result = func.apply(this, args);
        } catch (e) {
          state[stateKey].waiters.forEach(([resolve, reject]) => {
            reject(e);
          });
          delete state[stateKey];
          return;
        }

        state[stateKey].waiters.forEach(([resolve, reject]) => {
          resolve(state[stateKey].result);
        });
        delete state[stateKey];
      }, timeout);

      return new Promise((resolve, reject) => {
        state[stateKey].waiters.push([resolve, reject]);
      });
    };
  }

  // returns search results by post type
  const searchAllPosts = debounce(async query => {
    let results = {};
    (await getPosts(query)).forEach(post => {
      results[post.type] ||= [];
      results[post.type].push(post);
    });

    return results;
  }, 50);

  
  const PostPreviewComponent = {
    mount: (elem) => ({
      render: (data, metadata) => {
        if(data.content_html && data.content_html.length > 0) {
          elem.innerHTML = data.content_html
          return;
        }
        if(data.attachment_url && data.attachment_url.length > 0) {
          elem.innerHTML = `<img style="max-width: 100%; max-height: 100%;" src="${data.attachment_url}" />`;
          return;
        }

        elem.innerHTML = "<p style='color: gray;'>[ preview not available ]</p>";
        return;
      },
      unmount: () => {}
    })
  };
  
  window.CommandBar.addComponent(
    'post-preview',
    'Post HTML preview',
    PostPreviewComponent
  );

  window.CommandBarWPPlugin.POST_TYPES.forEach((postType) => {
    const label = postType.label;
    const name = postType.name;
    const postmeta_keys = postType.postmeta_keys;
    
    window.CommandBar.addRecords("post-" + name, [], {
      recordOptions: {
        categoryName: label
      },
      onInputChange: async query => (await searchAllPosts(query))[name] || [],
      searchableFields: ["title", /*"content",*/ ...(postmeta_keys || []).map(k => ({
        key: `meta.${k}`,
        label: metaKeyToLabel(k)
      }))],
      labelKey: 'title',
      descriptionKey: 'content',
      /*renderAs: 'grid',*/
      searchTabEnabled: postType.major_category,
      detail: {
        type: 'component',
        value: 'post-preview'
      }
    });

    window.CommandBar.addRecordAction(
      "post-" + name,
      {
        text: 'View',
        name: 'view_post-' + name,
        template: {
          type: 'link',
          value: '{{record.permalink}}',
          operation: 'self',
        },
      },
      true,
      false
    );

    window.CommandBar.addRecordAction(
      "post-" + name,
      {
        text: 'View (new tab)',
        name: 'view_post-' + name + "-newtab",
        template: {
          type: 'link',
          value: '{{record.permalink}}',
          operation: 'blank',
        }
      },
      false,
      false
    );


    if (name === 'post' || name === 'page' || name == 'product') {
      window.CommandBar.addRecordAction(
      "post-" + name,
      {
        text: 'Change title',
        name: 'change_title-' + name,
        arguments: {
          title: {
            'type': 'provided',
            'preselected_key': '{{record.title}}',
            'value': 'text',
            'order_key': 1,
            'label': 'Enter a title for this post'
          }
        },
        template: {
          type: 'callback',
          value: 'WP_post_edit',
          operation: 'self',
        },
        icon: 'edit'
      }, false, false);
    }
  });
})();