const wpPot = require('wp-pot');

const PLUGIN_NAME = 'UpStream Copy Project';
const LANGUAGE_DOMAIN = 'upstream-copy-project';

wpPot({
    destFile: `./src/languages/${LANGUAGE_DOMAIN}.pot`,
    domain: LANGUAGE_DOMAIN,
    package: PLUGIN_NAME,
    src: [
        './src/**/*.php'
    ]
});
