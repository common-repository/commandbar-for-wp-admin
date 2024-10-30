window.CommandBar.addCommand({
  category: 'Manage',
  text: 'Install a new plugin',
  name: 'install_a_new_plugin',
  arguments: {
    search: {
      'type': 'context',
      'value': 'plugins',
      'order_key': 0,
      'label': 'Search for a plugin to install'
    }
  },
  template: {
    type: 'link',
    value: 'plugin-install.php?tab=plugin-information&plugin={{search.slug}}',
    operation: 'blank',
  },
  icon: 'thunderbolt'
});

// --------------------------------------------------------------------------
// ---                     Gutenberg editor commands                      ---
// --------------------------------------------------------------------------

window.CommandBar.setCategoryConfig("Actions", { sort_key: -1 });

[{
  category: 'Actions',
  text: 'Publish',
  name: 'publish',
  arguments: {
   date: {
    'type': 'provided',
    'preselected_key': 'WP_core-editor_currentPost.date',
    'value': 'time',
    'order_key': 0,
    'label': 'When to publish?',
    'dateTimeArgumentTypeId': 1
   }
  },
  template: {
    type: 'callback',
    value: 'WP_currentPost_publish',
    operation: 'blank',
  },
  availability_rules: [
    {
      "type": "context",
      "operator": "isTrue",
      "field": "WP_core-editor_currentPost_hasPublishAction"
    },
    {
      "type": "context",
      "operator": "matchesRegex",
      "field": "WP_core-editor_currentPost.status",
      "value": "(draft|pending)"
    }
  ],
  icon: 'checksquare'
},
{
  category: 'Actions',
  text: 'Change title',
  name: 'change_title',
  arguments: {
   title: {
    'type': 'provided',
    'preselected_key': 'WP_core-editor_editedPostAttributes.title',
    'value': 'text',
    'order_key': 0,
    'label': 'Enter a title for this post'
   }
  },
  template: {
    type: 'callback',
    value: 'WP_currentPost_edit',
    operation: 'blank',
  },
  icon: 'edit'
},
{
  category: 'Actions',
  text: 'Switch to draft',
  name: 'switch_to_draft',
  arguments: {},
  template: {
    type: 'callback',
    value: 'WP_currentPost_switchToDraft',
    operation: 'blank',
  },
  availability_rules: [
    {
      "type": "context",
      "operator": "isTrue",
      "field": "WP_core-editor_currentPost_canSwitchToDraft"
    }
  ],
  icon: 'lock'
},
{
  category: 'Actions',
  text: 'Change categories',
  name: 'change_categories',
  arguments: {
   categories: {
    'type': 'context',
    'input_type': 'multi-select',
    'preselected_key': 'WP_core-editor_editedPostAttributes_categories',
    'value': 'WP_core_categories',
    'order_key': 0,
    'label': 'Type a category name'
   }
  },
  template: {
    type: 'callback',
    value: 'WP_currentPost_setCategories',
    operation: 'blank',
  },
  icon: 'edit'
},
{
  category: 'Actions',
  text: 'Insert image at cursor',
  name: 'add_image',
  arguments: {
   alt: {
    'type': 'provided',
    'input_type': 'default',
    'value': 'text',
    'order_key': 1,
    'label': '\'alt\' text for image'
   },
   caption: {
    'type': 'provided',
    'value': 'text',
    'order_key': 2,
    'label': 'Caption for image'
   },
   attachment: {
    'type': 'context',
    'value': 'post-attachment',
    'order_key': 0,
    'label': 'Search for an image'
   }
  },
  template: {
    type: 'callback',
    value: 'WP_currentPost_insertBlock_image',
    operation: 'blank',
  },
  icon: 'caretdown'
}].forEach((value, index) => {
  window.CommandBar.addCommand({...value, sort_key: index});
});

window.CommandBar.addRecords('WP_core-preview-device-types', [
  'Desktop',
  'Tablet',
  'Mobile'
].map(value => ({label: value, value, category: 'Actions'})), {recordOptions: {unfurl: true}});

(function() {
  const command = {
    text: 'Preview',
    category: 'Actions',
    name: 'preview',
    template: {
      type: 'callback',
      value: 'WP_core-edit-post_setPreviewDeviceType',
      operation: 'blank',
    },
    icon: '📱',
  };

  if(window.CommandBarWPPlugin.OPTIONS.DEFAULT_SHORTCUTS) {
    command['hotkey_mac'] = 'meta+shift+p';
    command['hotkey_win'] = 'meta+shift+p';
  }
  
  window.CommandBar.addRecordAction('WP_core-preview-device-types', command, true, true);
})();
