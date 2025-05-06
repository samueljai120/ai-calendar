module.exports = {
    extends: [
        '@wordpress/stylelint-config',
        'stylelint-config-standard'
    ],
    rules: {
        'at-rule-empty-line-before': null,
        'at-rule-no-unknown': null,
        'comment-empty-line-before': null,
        'declaration-property-unit-allowed-list': null,
        'font-weight-notation': null,
        'max-line-length': null,
        'no-descending-specificity': null,
        'rule-empty-line-before': null,
        'selector-class-pattern': null,
        'value-keyword-case': null,
        'selector-id-pattern': null,
        'declaration-block-no-redundant-longhand-properties': null,
        'color-function-notation': null
    },
    ignoreFiles: [
        'build/**',
        'dist/**',
        'node_modules/**',
        'vendor/**',
        '**/*.min.css'
    ]
}; 