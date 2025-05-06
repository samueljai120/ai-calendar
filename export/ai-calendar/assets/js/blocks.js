const { registerBlockType } = wp.blocks;
const { InspectorControls, useBlockProps, BlockControls, ToolbarGroup, ToolbarButton } = wp.blockEditor;
const { 
    TextControl, 
    DateTimePicker, 
    SelectControl, 
    RangeControl, 
    Button,
    __experimentalToolsPanelItem: ToolsPanelItem,
    __experimentalToolsPanel: ToolsPanel,
    CheckboxControl
} = wp.components;
const { Fragment, useState, useEffect } = wp.element;
const { useSelect, useDispatch } = wp.data;
const { store: coreStore } = wp.coreData;
const { store: editorStore } = wp.editor;
const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editor;
const { __ } = wp.i18n;

// Custom hook to handle editor panel state
const useEditorPanelState = (panelName) => {
    return useSelect(
        (select) => ({
            isEnabled: select(editorStore).isEditorPanelEnabled(panelName),
            isOpened: select(editorStore).isEditorPanelOpened(panelName),
        }),
        [panelName]
    );
};

// Register Event Details Block
registerBlockType('ai-calendar/event-details', {
    apiVersion: 3,
    title: __('Event Details', 'ai-calendar'),
    icon: 'calendar-alt',
    category: 'common',
    supports: {
        html: false,
        reusable: false,
        multiple: false,
        align: ['wide', 'full'],
        spacing: {
            margin: true,
            padding: true
        }
    },
    attributes: {
        startDate: {
            type: 'string',
            source: 'meta',
            meta: '_event_start'
        },
        endDate: {
            type: 'string',
            source: 'meta',
            meta: '_event_end'
        },
        location: {
            type: 'string',
            source: 'meta',
            meta: '_event_location'
        },
        recurring: {
            type: 'boolean',
            source: 'meta',
            meta: '_event_recurring'
        },
        recurrenceType: {
            type: 'string',
            source: 'meta',
            meta: '_event_recurrence_type'
        },
        recurrenceInterval: {
            type: 'number',
            source: 'meta',
            meta: '_event_recurrence_interval'
        },
        recurrenceEndDate: {
            type: 'string',
            source: 'meta',
            meta: '_event_recurrence_end_date'
        }
    },
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps({
            className: 'ai-calendar-event-details'
        });

        const { isEnabled, isOpened } = useEditorPanelState('ai-calendar/event-details');
        const { toggleEditorPanelOpened } = useDispatch(editorStore);

        useEffect(() => {
            if (!attributes.recurrenceType) {
                setAttributes({ recurrenceType: 'daily' });
            }
            if (!attributes.recurrenceInterval) {
                setAttributes({ recurrenceInterval: 1 });
            }
        }, []);
        
        return (
            <div {...blockProps}>
                <BlockControls group="block">
                    <ToolbarGroup>
                        <ToolbarButton
                            icon="edit"
                            label={__('Edit Event Details', 'ai-calendar')}
                            onClick={() => toggleEditorPanelOpened('ai-calendar/event-details')}
                        />
                    </ToolbarGroup>
                </BlockControls>
                <InspectorControls>
                    <ToolsPanel 
                        label={__('Event Settings', 'ai-calendar')}
                        resetAll={() => {
                            setAttributes({
                                startDate: null,
                                endDate: null,
                                location: '',
                                recurring: false,
                                recurrenceType: 'daily',
                                recurrenceInterval: 1,
                                recurrenceEndDate: null
                            });
                        }}
                    >
                        <ToolsPanelItem
                            hasValue={() => !!attributes.startDate}
                            label={__('Start Date & Time', 'ai-calendar')}
                            isShownByDefault={true}
                        >
                            <DateTimePicker
                                currentDate={attributes.startDate}
                                onChange={(date) => setAttributes({ startDate: date })}
                                __nextHasNoMarginBottom
                            />
                        </ToolsPanelItem>

                        <ToolsPanelItem
                            hasValue={() => !!attributes.endDate}
                            label={__('End Date & Time', 'ai-calendar')}
                            isShownByDefault={true}
                        >
                            <DateTimePicker
                                currentDate={attributes.endDate}
                                onChange={(date) => setAttributes({ endDate: date })}
                                __nextHasNoMarginBottom
                            />
                        </ToolsPanelItem>

                        <ToolsPanelItem
                            hasValue={() => !!attributes.location}
                            label={__('Location', 'ai-calendar')}
                            isShownByDefault={true}
                        >
                            <TextControl
                                value={attributes.location || ''}
                                onChange={(value) => setAttributes({ location: value })}
                                __nextHasNoMarginBottom
                            />
                        </ToolsPanelItem>

                        <ToolsPanelItem
                            hasValue={() => attributes.recurring !== undefined}
                            label={__('Recurring Event', 'ai-calendar')}
                            isShownByDefault={true}
                        >
                            <CheckboxControl
                                label={__('This is a recurring event', 'ai-calendar')}
                                checked={attributes.recurring}
                                onChange={(value) => setAttributes({ recurring: value })}
                                __nextHasNoMarginBottom
                            />
                        </ToolsPanelItem>

                        {attributes.recurring && (
                            <Fragment>
                                <ToolsPanelItem
                                    hasValue={() => !!attributes.recurrenceType}
                                    label={__('Recurrence Settings', 'ai-calendar')}
                                    isShownByDefault={true}
                                >
                                    <SelectControl
                                        value={attributes.recurrenceType}
                                        options={[
                                            { label: __('Daily', 'ai-calendar'), value: 'daily' },
                                            { label: __('Weekly', 'ai-calendar'), value: 'weekly' },
                                            { label: __('Monthly', 'ai-calendar'), value: 'monthly' },
                                            { label: __('Yearly', 'ai-calendar'), value: 'yearly' }
                                        ]}
                                        onChange={(value) => setAttributes({ recurrenceType: value })}
                                        __nextHasNoMarginBottom
                                    />
                                    <RangeControl
                                        label={__('Interval', 'ai-calendar')}
                                        value={attributes.recurrenceInterval}
                                        onChange={(value) => setAttributes({ recurrenceInterval: value })}
                                        min={1}
                                        max={30}
                                        __nextHasNoMarginBottom
                                    />
                                    <DateTimePicker
                                        currentDate={attributes.recurrenceEndDate}
                                        onChange={(date) => setAttributes({ recurrenceEndDate: date })}
                                        __nextHasNoMarginBottom
                                    />
                                </ToolsPanelItem>
                            </Fragment>
                        )}
                    </ToolsPanel>
                </InspectorControls>
                <div className="event-details-preview">
                    <h3>{__('Event Details', 'ai-calendar')}</h3>
                    <p><strong>{__('Start:', 'ai-calendar')}</strong> {attributes.startDate ? new Date(attributes.startDate).toLocaleString() : __('Not set', 'ai-calendar')}</p>
                    <p><strong>{__('End:', 'ai-calendar')}</strong> {attributes.endDate ? new Date(attributes.endDate).toLocaleString() : __('Not set', 'ai-calendar')}</p>
                    <p><strong>{__('Location:', 'ai-calendar')}</strong> {attributes.location || __('Not set', 'ai-calendar')}</p>
                    {attributes.recurring && (
                        <p><strong>{__('Recurring:', 'ai-calendar')}</strong> {attributes.recurrenceType} (every {attributes.recurrenceInterval})</p>
                    )}
                </div>
            </div>
        );
    },
    save: () => null
});

// Register Custom Fields Block
registerBlockType('ai-calendar/custom-fields', {
    apiVersion: 3,
    title: __('Event Custom Fields', 'ai-calendar'),
    icon: 'list-view',
    category: 'common',
    supports: {
        html: false,
        reusable: false,
        multiple: false,
        align: ['wide', 'full'],
        spacing: {
            margin: true,
            padding: true
        }
    },
    attributes: {
        customFields: {
            type: 'array',
            source: 'meta',
            meta: '_event_custom_fields',
            default: []
        }
    },
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps({
            className: 'ai-calendar-custom-fields'
        });
        const [newFieldKey, setNewFieldKey] = useState('');
        const [newFieldValue, setNewFieldValue] = useState('');

        const addCustomField = () => {
            if (!newFieldKey || !newFieldValue) return;

            const updatedFields = [...(attributes.customFields || []), {
                key: newFieldKey,
                value: newFieldValue
            }];

            setAttributes({ customFields: updatedFields });
            setNewFieldKey('');
            setNewFieldValue('');
        };

        const removeCustomField = (index) => {
            const updatedFields = [...(attributes.customFields || [])];
            updatedFields.splice(index, 1);
            setAttributes({ customFields: updatedFields });
        };

        const updateCustomField = (index, key, value) => {
            const updatedFields = [...(attributes.customFields || [])];
            updatedFields[index] = { ...updatedFields[index], [key]: value };
            setAttributes({ customFields: updatedFields });
        };

        return (
            <div {...blockProps}>
                <div className="custom-fields-editor">
                    <h3>{__('Custom Fields', 'ai-calendar')}</h3>
                    
                    {(attributes.customFields || []).map((field, index) => (
                        <div key={index} className="custom-field-row">
                            <TextControl
                                value={field.key}
                                onChange={(value) => updateCustomField(index, 'key', value)}
                                placeholder={__('Field Name', 'ai-calendar')}
                                __nextHasNoMarginBottom
                            />
                            <TextControl
                                value={field.value}
                                onChange={(value) => updateCustomField(index, 'value', value)}
                                placeholder={__('Field Value', 'ai-calendar')}
                                __nextHasNoMarginBottom
                            />
                            <Button
                                variant="secondary"
                                isDestructive
                                onClick={() => removeCustomField(index)}
                                icon="trash"
                            />
                        </div>
                    ))}

                    <div className="add-custom-field">
                        <TextControl
                            value={newFieldKey}
                            onChange={setNewFieldKey}
                            placeholder={__('New Field Name', 'ai-calendar')}
                            __nextHasNoMarginBottom
                        />
                        <TextControl
                            value={newFieldValue}
                            onChange={setNewFieldValue}
                            placeholder={__('New Field Value', 'ai-calendar')}
                            __nextHasNoMarginBottom
                        />
                        <Button
                            variant="primary"
                            onClick={addCustomField}
                            disabled={!newFieldKey || !newFieldValue}
                        >
                            {__('Add Field', 'ai-calendar')}
                        </Button>
                    </div>
                </div>
            </div>
        );
    },
    save: () => null
}); 