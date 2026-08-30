
$.fn.clearInputButton = function () {

    // Loop trough all
    $.each($(this),function(i, el){

        const $input = $(el);

            // Prevent double-initialization
            if ($input.parent().hasClass('clear-input-wrapper')) {
                return;
            }

            // Wrap the input to position the absolute button inside
            $input.wrap('<div class="clear-input-wrapper position-relative d-inline-block w-100"></div>');
            const $wrapper = $input.parent();

            // Create the clear button
            const $btn = $('<button type="button" class="clear-input-btn btn btn-link text-muted position-absolute end-0 pe-2 border-0 bg-transparent" style="display: none; z-index: 5; font-size: 24px; top: -1px;"><ion-icon class="text-muted" name="close-outline"></ion-icon></button>');

            // Add right padding to bootstrap input to make room for the button
            $input.css('padding-right', '2.25rem');

            // Append button to wrapper
            $wrapper.append($btn);

            // Toggle visibility function
            const toggleButtonVisibility = function () {
                if ($input.val().length > 0) {
                    $btn.show();
                } else {
                    $btn.hide();
                }
            };

            // Run on load in case input already has a value (e.g., prefilled or browser autofill)
            toggleButtonVisibility();

            // Event Listeners
            $input.on('input change keyup', function () {
                toggleButtonVisibility();
            });

            // Clear input on button click and trigger events so other scripts notice
            $btn.on('click', function (e) {
                e.preventDefault();
                $input.val('').trigger('input').trigger('change').focus();

                if ($input.is('[data-input-clear-button-submit]')) {
                    const $form = $input.closest('form');
                    if ($form.length) {
                        $form.submit();
                    }
                }

                toggleButtonVisibility();
            });

    });
};
