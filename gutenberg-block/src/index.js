import { registerPlugin } from '@wordpress/plugins';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { useSelect, select, dispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import { store as coreDataStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { store as editPostStore } from '@wordpress/edit-post';
import { useDispatch } from '@wordpress/data';

function CommandbarGutenbergCurrentPostIntegration({categoriesById}) {
    const {
        post,
        isCurrentPostPublished,
        isCurrentPostScheduled
    } = useSelect(select => {
        const e = select(editorStore);
        const c = select(coreDataStore);

        return {
            post: e.getCurrentPost(),
            isCurrentPostPublished: e.isCurrentPostPublished(),
            isCurrentPostScheduled: e.isCurrentPostScheduled(),
            categories: c.getEntityRecords('taxonomy', 'category', {
                per_page: -1,
                orderby: 'name',
                order: 'asc',
                _fields: 'id,name,parent',
                context: 'view',
            }) || []
        }
    });

    const editedPostAttributes = useSelect(select => ({
        status: select(editorStore).getEditedPostAttribute('status'),
        title: select(editorStore).getEditedPostAttribute('title'),
        categories: select(editorStore).getEditedPostAttribute('categories')
    }));

    const { editEntityRecord, saveEditedEntityRecord } = useDispatch(coreDataStore);
    const { editPost } = useDispatch(editorStore);

    useEffect(() => {
        if (post) {
            window.CommandBar.addContext('WP_core-editor_currentPost', post);

            // https://github.com/WordPress/gutenberg/blob/4834fad30e39e47d71a93e2e08d31b3b856711e1/packages/edit-post/src/components/header/post-publish-button-or-toggle.js#L86
            window.CommandBar.addContext('WP_core-editor_currentPost_hasPublishAction', Boolean(post['_links']?.['wp:action-publish']));

            // https://github.com/WordPress/gutenberg/blob/368f8545c8c495681a937d421c0056843a3d88cf/packages/editor/src/components/post-switch-to-draft-button/index.js#L27
            window.CommandBar.addContext('WP_core-editor_currentPost_canSwitchToDraft', isCurrentPostPublished || isCurrentPostScheduled);
        }

        return () => {
            window.CommandBar.removeContext('WP_core-editor_currentPost');
            window.CommandBar.removeContext('WP_core-editor_currentPost_hasPublishAction');
            window.CommandBar.removeContext('WP_core-editor_currentPost_canSwitchToDraft');
        }
    }, [post, isCurrentPostPublished, isCurrentPostScheduled]);

    useEffect(() => {
        if (editedPostAttributes) {
            const categories = editedPostAttributes.categories;

            window.CommandBar.addContext('WP_core-editor_editedPostAttributes', editedPostAttributes);

            if (categories) {
                window.CommandBar.addContext('WP_core-editor_editedPostAttributes_categories', categories.map(categoryId => categoriesById[categoryId]));
            }
        }

        return () => {
            window.CommandBar.removeContext('WP_core-editor_editedPostAttributes');
            window.CommandBar.removeContext('WP_core-editor_editedPostAttributes_categories');
        }
    }, [editedPostAttributes]);


    useEffect(() => {
        window.CommandBar.addCallback('WP_currentPost_edit', (args, context) => {
            editEntityRecord('postType', post.type, post.id, args);
        });

        window.CommandBar.addCallback('WP_currentPost_publish', async (args, context) => {
            if (args.date) {
                editEntityRecord('postType', post.type, post.id, { date: args.date });
            }

            editEntityRecord('postType', post.type, post.id, { status: 'future' });

            await saveEditedEntityRecord('postType', post.type, post.id);
        });

        window.CommandBar.addCallback('WP_currentPost_switchToDraft', async (args, context) => {
            editEntityRecord('postType', post.type, post.id, { status: 'draft' });

            await saveEditedEntityRecord('postType', post.type, post.id);
        });

        return () => {
            window.CommandBar.removeCallback('WP_currentPost_edit');
            window.CommandBar.removeCallback('WP_currentPost_publish');
            window.CommandBar.removeCallback('WP_currentPost_switchToDraft');
        };
    }, [post, editEntityRecord, saveEditedEntityRecord]);

    useEffect(() => {
        window.CommandBar.addCallback('WP_currentPost_insertBlock_image', (args, context) => {
            const { attachment, alt, caption } = args;
            const { index, rootClientId } = select('core/block-editor').getBlockInsertionPoint();

            // see https://github.com/WordPress/gutenberg/blob/trunk/packages/block-library/src/image/block.json
            dispatch('core/block-editor').insertBlock(createBlock('core/image', {
                url: attachment.attachment_url,
                alt,
                caption
            }), index, rootClientId);
        });

        return () => {
            window.CommandBar.removeCallback('WP_currentPost_insertBlock_image');
        }
    }, [])

    useEffect(() => {
        window.CommandBar.addCallback('WP_currentPost_setCategories', (args, context) => {
            if (!args.categories) return;

            editPost({ 'categories': args.categories.map(category => category.id) });
        });

        return () => {
            window.CommandBar.removeCallback('WP_currentPost_setCategories');
        }
    }, [editPost]);

    // https://github.com/WordPress/gutenberg/blob/2c5b236d0e18419411a99f03e297e87decb76f27/packages/edit-post/src/components/device-preview/index.js#L44
    const { __experimentalSetPreviewDeviceType: setPreviewDeviceType } = useDispatch(editPostStore);
    const deviceType = useSelect(select => select(editPostStore).__experimentalGetPreviewDeviceType());

    useEffect(() => {
        window.CommandBar.addCallback('WP_core-edit-post_setPreviewDeviceType', (args, context) => {
            // args.record.value is "Desktop", "Tablet", or "Mobile"
            const device = args?.record?.value || 'Desktop';
            
            setPreviewDeviceType(device)
        });

        window.CommandBar.addContext("WP_core-edit-post_previewDeviceType", deviceType)

        return () => {
            window.CommandBar.removeCallback('WP_core-edit-post_setPreviewDeviceType');
            window.CommandBar.removeContext('WP_core-edit-post_previewDeviceType');
        }
    }, [setPreviewDeviceType, deviceType]);

    return null;
}

function CommandbarWordpressIntegration({categoriesById}) {
    useEffect(() => {
        window.CommandBar.addContext('WP_core_categories', Object.values(categoriesById));

        return () => {
            window.CommandBar.removeContext('WP_core_categories');
        }
    }, [categoriesById]);

    useEffect(() => {
        window.CommandBar.addCallback('WP_post_edit', async (args, context) => {
            const { post: _post, record: _record, ...edits } = args;
            const post = _post || _record;

            if (!post) return false;

            const p = new wp.api.models.Post({ id: post.id });
            Object.entries(edits).forEach(([key, value]) => {
                console.log("Edits: ", key, value);
                p.set(key, value);
            })
            p.save();

            return true;

        });

        return () => {
            window.CommandBar.removeCallback('WP_post_edit');
        }
    }, [])


    return null;
}


function CommandbarIntegration() {
    const hasCurrentPost = useSelect(select => select(editorStore).getCurrentPost()?.id != null );

    const categories = useSelect(select => select(coreDataStore).getEntityRecords('taxonomy', 'category', {
        per_page: -1,
        orderby: 'name',
        order: 'asc',
        _fields: 'id,name,parent',
        context: 'view',
    })) || [];

    const flattenedCategoriesById = useMemo(() => {
        let categoriesById = {};
        let _flattenedCategoriesById = {};

        categories.forEach(category => categoriesById[category.id] = category);

        let _flattenedCategories = categories.map(category => {
            let currentCategory = category;
            let flatName = [category.name];
            while (categoriesById[currentCategory.parent]) {
                flatName.unshift(categoriesById[currentCategory.parent].name)
                currentCategory = categoriesById[currentCategory.parent];
            }

            return {
                ...category,
                label: flatName.join(' / ')
            };
        });

        _flattenedCategories = [..._flattenedCategories].sort((a, b) => {
            // force "Uncategorized" (default WordPress category) to sort last
            if (a === "Uncategorized") return 1;
            if (b === "Uncategorized") return -1;

            return a.label.localeCompare(b.label);
        });

        _flattenedCategories.forEach(category => _flattenedCategoriesById[category.id] = category);

        return _flattenedCategoriesById;
    }, [categories]);


    return <>
        {hasCurrentPost && <CommandbarGutenbergCurrentPostIntegration categoriesById={flattenedCategoriesById} /> }
        <CommandbarWordpressIntegration categoriesById={flattenedCategoriesById} />
    </>;
}

// just render out to an unmnounted DIV; since we just want to subscribe to the stores and add context / callbacks
ReactDOM.render(<CommandbarIntegration />, document.createElement('div'));
