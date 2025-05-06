const { registerBlockType } = wp.blocks;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, RangeControl, ToggleControl, SelectControl } = wp.components;
const { __ } = wp.i18n;

registerBlockType('ai-calendar/calendar', {
    title: __('AI Calendar'),
    icon: 'calendar-alt',
    category: 'widgets',
    attributes: {
        eventsPerDay: {
            type: 'number',
            default: 2
        },
        showEventTime: {
            type: 'boolean',
            default: true
        },
        showEventLocation: {
            type: 'boolean',
            default: true
        },
        firstDayOfWeek: {
            type: 'number',
            default: 0
        }
    },
    
    edit: function(props) {
        const { attributes, setAttributes } = props;
        
        return [
            wp.element.createElement(InspectorControls, { key: 'inspector' },
                wp.element.createElement(PanelBody, { title: __('Calendar Settings') },
                    wp.element.createElement(RangeControl, {
                        label: __('Events per day'),
                        value: attributes.eventsPerDay,
                        onChange: (value) => setAttributes({ eventsPerDay: value }),
                        min: 1,
                        max: 5
                    }),
                    wp.element.createElement(ToggleControl, {
                        label: __('Show event time'),
                        checked: attributes.showEventTime,
                        onChange: (value) => setAttributes({ showEventTime: value })
                    }),
                    wp.element.createElement(ToggleControl, {
                        label: __('Show event location'),
                        checked: attributes.showEventLocation,
                        onChange: (value) => setAttributes({ showEventLocation: value })
                    }),
                    wp.element.createElement(SelectControl, {
                        label: __('First day of week'),
                        value: attributes.firstDayOfWeek,
                        options: [
                            { label: __('Sunday'), value: 0 },
                            { label: __('Monday'), value: 1 }
                        ],
                        onChange: (value) => setAttributes({ firstDayOfWeek: parseInt(value) })
                    })
                )
            ),
            wp.element.createElement('div', { className: 'ai-calendar-block-preview' },
                wp.element.createElement('div', { className: 'calendar-preview-placeholder' },
                    __('AI Calendar')
                )
            )
        ];
    },
    
    save: function() {
        return null; // Dynamic block, render in PHP
    }
}); 