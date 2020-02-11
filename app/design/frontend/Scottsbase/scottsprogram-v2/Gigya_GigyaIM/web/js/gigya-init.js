define([
    'jquery',
], function ($) {
    'use strict';

    return function (config) {
        window.gigyaInit = window.gigyaInit || [];

        let gigInt = setInterval(() => {
            if (document.querySelector('#gigya-reset-password-form')) {
                const form = document.querySelector('#gigya-reset-password-form');
                const input = form.querySelector('input[type="text"].gigya-input-text');
                const button = form.querySelector('.gigya-input-submit');

                button.classList.add('sp-button--inactive');

                input.addEventListener('input', e => {
                    /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/.test(e.target.value) ?
                    button.classList.remove('sp-button--inactive') :
                    button.classList.add('sp-button--inactive');
                });

                clearInterval(gigInt);
            }
        }, 50);

        const customLangParams = {
            email_already_exists: `
                <div class="sp-gigya-error sp-form-error sp-font-bold sp-mb-4 sp--mt-2">This email is already associated with an account.</div>
                <div class="sp-gigya-error sp-form-error sp-mb-4">
                    You can log in to your account if you've created one with the My Lawn app, Scotts.com, the My Garden app, the Blossom Smart Watering app, the Gro Connect app, or the MiracleGro Twelve app.
                </div>
                <div class="sp-flex">
                    <a class="sp-gigya-link sp-mr-4" href="javascript: void(0)" onclick="
                        gigyaChangeScreen('gigya-login-screen')
                    ">Log In</a>
                    <a class="sp-gigya-link" href="javascript: void(0)" onclick="
                        gigyaChangeScreen('gigya-forgot-password-screen')
                    ">Forgot Password</a>
                </div>
            `
        };

        const screenParams = {
            screenSet: config.screenSet,
            containerID: config.containerID,
            startScreen: config.startScreen || (window.location.hash && window.location.hash === '#forgot' ? 'gigya-forgot-password-screen' : 'gigya-login-screen'),
            mobileScreenSet: config.mobileScreenSet,
            customLang: customLangParams,
            onAfterScreenLoad() { window.scrollTo(0, 0) },
        };

        window.gigyaChangeScreen = (screen) => {
            gigya.accounts.switchScreen({
                screen: screen,
                screenSet: config.screenSet,
                containerID: config.containerID,
            });
        };

        window.gigyaInit.push({
            'function': 'accounts.showScreenSet',
            parameters: screenParams,
        });
    }
});
