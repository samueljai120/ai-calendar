module.exports = {
    root: true,
    extends: [
        'plugin:@wordpress/eslint-plugin/recommended',
        'plugin:@wordpress/eslint-plugin/esnext',
        'plugin:@wordpress/eslint-plugin/jsx-a11y'
    ],
    env: {
        browser: true,
        es6: true,
        jquery: true
    },
    parserOptions: {
        requireConfigFile: false,
        ecmaVersion: 2021,
        sourceType: 'module',
        ecmaFeatures: {
            jsx: true
        }
    },
    globals: {
        wp: true,
        aiCalendar: true,
        aiCalendarAdmin: true
    },
    rules: {
        'import/no-unresolved': 'off',
        'import/no-extraneous-dependencies': 'off',
        'import/extensions': 'off',
        'react/react-in-jsx-scope': 'off',
        'react/jsx-filename-extension': 'off',
        'jsx-a11y/no-noninteractive-element-interactions': 'warn',
        'jsx-a11y/click-events-have-key-events': 'warn'
    },
    settings: {
        react: {
            version: 'detect'
        }
    }
}; 