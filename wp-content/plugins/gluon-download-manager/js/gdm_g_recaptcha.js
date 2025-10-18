/**
 * gdm_reCaptcha
 * @type {{Object}}
 */
var gdm_reCaptcha = function () {
    var recaptcha = document.getElementsByClassName("g-recaptcha");
    for (var i = 0; i < recaptcha.length; i++) {
	grecaptcha.render(recaptcha.item(i), {"sitekey": gdm_recaptcha_opt.site_key});
    }
};

/**
 * for sdm recaptcha v3. This gets called when google reCaptcha cdn is loaded.
 */
function gdm_reCaptcha_v3(){
    grecaptcha.ready(function() {
        document.dispatchEvent(new CustomEvent('gdm_reCaptcha_v3_ready'));
    });
}

document.addEventListener('gdm_reCaptcha_v3_ready', async function (){
    const token = await grecaptcha.execute(
        gdm_recaptcha_opt.site_key,
        { action: 'gdm_download' }
    );

    const v3recaptchaInputs = document.querySelectorAll('.gdm-g-recaptcha-v3-response');
    v3recaptchaInputs?.forEach(function(resp_input){
        resp_input.value = token
    });
})