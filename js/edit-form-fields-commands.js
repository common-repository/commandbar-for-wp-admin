(() => {
    const form = document.querySelector('form#post');
    if (!form) return;
    const submitButton = form.querySelector('input[type=submit][name=save]');

    const submit = () => {
        if (submitButton) return submitButton.click();

        form.submit();
    }

    const inputs = Array.from(form.querySelectorAll('input'));
    if (inputs.length === 0) return;

    const cleanLabel = _label => {
        let label = _label.trim();

        if (label.match(/[.:?]$/)) {
            return label.slice(0, label.length - 1);
        }

        return label;
    };

    const getFieldsWithLabelByType = (...types) => inputs.map(node => {
        const label = document.querySelector('label[for="' + node.id + '"]')?.innerText;

        if (!types.includes(node.type)) return null;

        if (label) {
            return [label.trim(), node];
        }

        return null;
    }).filter(Boolean);

    const labeledFields = {
        'text': {
            fields: getFieldsWithLabelByType('text', 'url', 'tel', 'number', 'range', 'email', 'color'),
        },
        'textarea': {
            fields: Array.from(form.querySelectorAll('textarea')).map(node => {
                const label = document.querySelector('label[for="' + node.id + '"]')?.innerText;

                if (label) {
                    return [label.trim(), node];
                }

                return null;
            }).filter(Boolean),
            longtext: true
        },
        'checkbox': {
            fields: getFieldsWithLabelByType('checkbox'),
            values: ['yes', 'no'],
            callback: ({ value, record: { node } }) => {
                node.checked = (value === 'yes');
                node.blur();
                submit();
            }
        },
        'date': {
            fields: getFieldsWithLabelByType('date'),
            date: true,
        },
        'time': {
            fields: getFieldsWithLabelByType('time'),
            time: true
        },
        'datetime': {
            fields: getFieldsWithLabelByType('datetime-local'),
            datetime: true
        }
    };

    window.CommandBar.addCallback('handle_change_field_value',
        ({ value, record: { node } }) => {
            node.value = value;
            node.blur();
            submit();
        }
    );

    Object.entries(labeledFields).forEach(([type, descriptor]) => {
        let callbackFn = 'handle_change_field_value';
        const contextKey = `form_field_${type}`;

        if (descriptor.callback) {
            callbackFn = `handle_change_field_value_${type}`;
            window.CommandBar.addCallback(callbackFn, descriptor.callback);
        }

        let argument = {
            'type': 'provided',
            'preselected_key': '{{record.value}}',
            'value': 'text',
            'label': 'enter new value',
            'order_key': 1,
        }

        if (descriptor.longtext) {
            argument = { ...argument, 'input_type': 'longtext' }
        }

        if (descriptor.values) {
            argument = {
                'type': 'set',
                'value': descriptor.values,
                'order_key': 1
            };
        }

        if (descriptor.datetime || descriptor.date || descriptor.time) {
            let dateTimeArgumentTypeId;
            if (descriptor.datetime) {
                dateTimeArgumentTypeId = 1;
            } else if (descriptor.date) {
                dateTimeArgumentTypeId = 2;
            } else if (descriptor.time) {
                dateTimeArgumentTypeId = 3;
            }

            argument = {
                'type': 'provided',
                'value': 'time',
                'order_key': 1,
                'label': 'Enter a date or time, like Friday at 4pm',
                dateTimeArgumentTypeId
            }
        }


        window.CommandBar.addRecords(
            contextKey,
            descriptor.fields.map(([label, node]) => ({
                category: "Actions",
                icon: '✏️',
                label: cleanLabel(label),
                value: node.value,
                description: descriptor.values ? descriptor.values.join(' / ') : undefined,
                node
            })),
            {
                recordOptions: { unfurl: true },
                labelKey: 'label', descriptionKey: 'description'
            }
        );

        window.CommandBar.addRecordAction(contextKey, {
            name: `Change_${type}`,
            text: "Change",
            template: {
                type: 'callback',
                value: callbackFn
            },
            arguments: {
                value: argument
            },
        });
    });
})();