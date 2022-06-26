# moodle-badge-linkedin
Details on how to imlpement a renderer/customscript to allow Badges to be shared on LinkedIn. There should be an organisation set up on LinkedIn already that matches the badge issuer.

LinkedIn lets you post a 'certification' through a public form as long as you are already logged in. It doesn't use the API or implement any authentication. It opens `https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME` with parameters, allowing the user to then change or input other details.

## Modifying config.php

This plugin uses Custom Scripts.

    > Enabling this will allow custom scripts to replace existing moodle scripts.
    > For example: if $CFG->customscripts/course/view.php exists then
    > it will be used instead of $CFG->wwwroot/course/view.php
    > At present this will only work for files that include config.php and are called
    > as part of the url (index.php is implied).
    > Some examples are:
    >      http://my.moodle.site/course/view.php
    >      http://my.moodle.site/index.php
    >      http://my.moodle.site/admin            (index.php implied)
    > Custom scripts should not include config.php
    > Warning: Replacing standard moodle scripts may pose security risks and/or may not
    > be compatible with upgrades. Use this option only if you are aware of the risks
    > involved.
    > Specify the full directory path to the custom scripts
    >      $CFG->customscripts = '/home/example/customscripts';

I find it useful to have customscripts inside the moodle folder. Add `$CFG->customscripts = __DIR__ . '/customscripts';` to your site `config.php`

## Add customscript

Inside the configured customscript folder, add a `badges` folder and within that the `badges.php` file. This is a copy of the file from the core folder modified with a change that allows the badge image to be served without authentication. The file **MUST** end with `die();` to prevent the original file executing. If you upgrade Moodle, remember to diff/upgrade the customscripts too.


## Modifying your theme renderer

Inside the theme `config.php` file ensure that `$THEME->rendererfactory = 'theme_overridden_renderer_factory';` is set.

Create or modify the `classes` folder to contain the core_badges_render.php; to make autoloading work the **FILE NAME** must be class you want to override (e.g. core_SUBSYSTEM_renderer) whilst the **CLASS NAME** within the file must be `theme_THEME_FOLDER_core_SUBSYSTEM_renderer extends \core_SUBSYSTEM_renderer` were THEME_FOLDER is the name-including-underscores that the theme exists in, and SUBSYSTEM is the name of the subsystem or plugin you are overriding (the file name).

You don't need a namespace. You don't need to include the /badges/renderer.php file.

Modify the *LINKEDIN_ORGANISATION* constant to be the EXACT organisation name as LinkedIn sees it.

## Purge Cache

If you don't purge caches the files probably won't autoload. 