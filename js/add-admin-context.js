// Register plugin search function in CommandBar
window.CommandBar.addArgumentChoices("plugins", [],
    {
        onInputChange: async (query) => {
            const response = await fetch('https://api.wordpress.org/plugins/info/1.2/?action=query_plugins&request[search]=' + encodeURIComponent(query));
            const { plugins } = await response.json();

            // https://stackoverflow.com/questions/7394748/whats-the-right-way-to-decode-a-string-that-has-special-html-entities-in-it/7394787#7394787
            var decodeHTML = function (html) {
                var txt = document.createElement('textarea');
                txt.innerHTML = html;
                return txt.value;
            };
            
            return plugins.map(plugin => 
                ({
                    ...plugin, 
                    name: decodeHTML(plugin['name']), 
                    icon: (plugin['icons'] || {})['1x'] || (plugin['icons'] || {})['2x']
                })
            );
        },

        searchableFields: [
            "content",
            "status"
        ],

        renderAs: 'grid',
        labelKey: 'name',
        descriptionKey: 'short_description'
    }
);

