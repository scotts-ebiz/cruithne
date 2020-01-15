define([
    'uiComponent'
], function (Component) {

    return Component.extend({
        initialize(config) {
            const self = this;
            let interval = setInterval( function() {
                try {
                    self.loadGigyaScreenset();
                    clearInterval(interval);
                } catch (err) {}
            }, 250);
        },

        loadGigyaScreenset() {
            gigya.accounts.showScreenSet({
                screenSet: 'ScottsProgram-RegistrationLogin',
                startScreen:'gigya-reset-password-screen',
                containerID: 'gigya-screen',
                authFlow: 'redirect'
            });
        }
    });
});
