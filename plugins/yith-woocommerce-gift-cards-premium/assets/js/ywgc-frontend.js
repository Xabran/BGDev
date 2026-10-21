jQuery(document).ready(function ($) {


    var init_plugin = function () {
        if (jQuery().prettyPhoto) {
            $('[rel="prettyPhoto[ywgc-choose-design]"]').prettyPhoto({

                hook              : 'rel',
                social_tools      : false,
                theme             : 'pp_woocommerce',
                default_width     : '80%',
                default_height    : '100%',
                horizontal_padding: 20,
                opacity           : 0.8,
                deeplinking       : false,
                keyboard_shortcuts: true,
                allow_resize      : false
            });
        }
    };

    var hide_on_gift_as_present = function () {
        if ($('input[name="ywgc-as-present"]').length) {
            $('.ywgc-generator').hide();
            show_gift_card_editor(false);
        }
    }

    init_plugin();

    show_hide_add_to_cart_button();

    hide_on_gift_as_present();

    $(document).on('click', '.ywgc-choose-design.ywgc-template', function (e) {
        init_plugin();
    });

    $(document).on('click', 'a.ywgc-show-category', function (e) {

        e.preventDefault;
        var current_category = $(this).data("category-id");

        //  highlight the selected category
        $('a.ywgc-show-category').removeClass('ywgc-category-selected');
        $(this).addClass('ywgc-category-selected');

        //  Show only the design of the selected category
        if ('all' !== current_category) {
            $('.ywgc-design-item').hide();
            $('.ywgc-design-item.' + current_category).show();
        }
        else {
            $('.ywgc-design-item').show();

        }
        return false;
    });

    $(document).on('ywgc-picture-changed', function (event, type, id) {
            switch (type) {
                case 'default':
                case 'custom':
                case 'template':
                    $('#ywgc-design-type').val(type);
                    $('#ywgc-template-design').val(id);
                    break;
            }
        }
    );

    $(document).on('click', 'button.ywgc-choose-template', function (e) {

        var id = $(this).data('design-id');
        var design_url = $(this).data('design-url');
        $('#ywgc-main-image').attr('src', design_url);
        $(document).trigger('ywgc-picture-changed', ['template', id]);
        $.prettyPhoto.close();
    });

    $(document).on('click', 'a.ywgc-show-giftcard', show_coupon_form);

    function show_coupon_form() {
        $('.ywgc-enter-code').slideToggle(400, function () {
            $('.ywgc-enter-code').find(':input:eq(0)').focus();
        });
        return false;
    }

    /** Show the edit gift card button */
    $("button.ywgc-do-edit").css("display", "inline");

    /** init datepicker */
    $("div.ywgc-generator .datepicker").datepicker({dateFormat: "yy-mm-dd", minDate: +1, maxDate: "+1Y"});

    function update_gift_card_amount(amount) {

        $("div.ywgc-card-amount span.amount").text(amount);

    }

    function show_gift_card_editor(val) {
        $('button.gift_card_add_to_cart_button').attr('disabled', !val);

        if (val) {
            //  Set the flag required on <input> elements
            $('input[name="ywgc-recipient-email"]').add('input[name="ywgc-sender-name"]').prop("required", true);
        }
        else {
            //  Unset the flag required on <input> elements
            $('input[name="ywgc-recipient-email"]').add('input[name="ywgc-sender-name"]').prop("required", false);
        }
    }

    function show_hide_add_to_cart_button() {

        var select_element = $(".gift-cards-list select");
        var gift_this_product = $('#give-as-present');

        if (!gift_this_product.length) {
            $('.gift-cards-list input.ywgc-manual-amount').addClass('hidden');

            var amount = 0;
            if ((select_element.length == 0) || ("-1" == select_element.val())) {
                /* the user should enter a manual value as gift card amount */
                var manual_amount_element = $('.gift-cards-list input.ywgc-manual-amount');
                manual_amount_element.removeClass('hidden');

                /* If the user entered a valid amount, show "add to cart" button and gift card
                 editor.
                 */
                if (manual_amount_element.val()) {
                    // manual amount is a valid numeric value
                    show_gift_card_editor(true);

                    amount = manual_amount_element.val();
                    amount = accounting.unformat(amount, ywgc_data.mon_decimal_point);

                    if (amount <= 0) {
                        show_gift_card_editor(false);
                    }
                    else {
                        amount = accounting.formatMoney(amount, {
                            symbol: ywgc_data.currency_format_symbol,
                            decimal: ywgc_data.currency_format_decimal_sep,
                            thousand: ywgc_data.currency_format_thousand_sep,
                            precision: ywgc_data.currency_format_num_decimals,
                            format: ywgc_data.currency_format
                        });

                        show_gift_card_editor(true);
                    }
                }
                else {
                    show_gift_card_editor(false);
                }
            }
            else if (!select_element.val()) {
                show_gift_card_editor(false);
            }
            else {
                show_gift_card_editor(true);
                amount = select_element.children("option:selected").text();
            }
            update_gift_card_amount(amount);
        }
    }

    $(document).on('input', '.gift-cards-list input.ywgc-manual-amount', function (e) {
        show_hide_add_to_cart_button();
    });

    function add_recipient() {
        var last = $('div.ywgc-single-recipient').last();
        var new_div = '<div class="ywgc-single-recipient">\
            <input type="email" name="ywgc-recipient-email[]" class="ywgc-recipient" required/> \
            <a href="#" class="remove-recipient hide-if-alone">x</a> \
            </div>';

        last.after(new_div);


        //  show the remove recipient links
        $("a.remove-recipient").css('visibility', 'visible');

        $("div.gift_card_template_button input[name='quantity']").css("display", "none");

        //  show a message for quantity disabled when multi recipients is entered
        if (!$("div.gift_card_template_button div.multi-recipients").length) {
            $("div.gift_card_template_button div.quantity").after("<div class='multi-recipients'><span>" + ywgc_data.multiple_recipient + "</span></div>");
        }
    }

    function remove_recipient(element) {
        //  remove the element
        $(element).parent("div.ywgc-single-recipient").remove();

        //  Avoid the deletion of all recipient
        var emails = $('input[name="ywgc-recipient-email[]"');
        if (emails.length == 1) {
            //  only one recipient is entered...
            $("a.hide-if-alone").css('visibility', 'hidden');
            $("div.gift_card_template_button input[name='quantity']").css("display", "inherit");

            $("div.multi-recipients").remove();
        }
    }

    $(document).on('click', 'a.add-recipient', function (e) {
        e.preventDefault();
        add_recipient();
    });

    $(document).on('click', 'a.remove-recipient', function (e) {
        e.preventDefault();
        remove_recipient($(this));
    });

    $(document).on('input', '#ywgc-edit-message', function (e) {
        $(".ywgc-card-message").text($('#ywgc-edit-message').val());
    });

    $(document).on('change', '.gift-cards-list select', function (e) {
        show_hide_add_to_cart_button();
    });

    $(document).on('click', 'a.customize-gift-card', function (e) {
        e.preventDefault();
        $('div.summary.entry-summary').after('<div class="ywgc-customizer"></div>');
    });

    /** Set to default the image used on the gift card editor on product page */
    $(document).on('click', '.ywgc-default-picture', function (e) {
        e.preventDefault();
        var control = $('#ywgc-upload-picture');
        control.replaceWith(control = control.clone(true));
        $('.ywgc-main-image img.ywgc-main-image').attr('src', ywgc_data.default_gift_card_image);

        //  Reset style if previously a custom image was used
        $(".ywgc-main-image").css("background-color", "");
        $("div.gift-card-too-small").remove();

        $(document).trigger('ywgc-picture-changed', ['default']);
    });

    /** Show the custom file choosed by the user as the image used on the gift card editor on product page */
    $(document).on('click', '.ywgc-custom-picture', function (e) {
        $('#ywgc-upload-picture').click();
    });

    $('#ywgc-upload-picture').on('change', function () {
        var preview_image = function (file) {
            var oFReader = new FileReader();
            oFReader.readAsDataURL(file);

            oFReader.onload = function (oFREvent) {
                document.getElementById("ywgc-main-image").src = oFREvent.target.result;
                $(document).trigger('ywgc-picture-changed', ['custom']);

                /** Check the size of the file choosed and notify it to the user if it is too small */
                if ($("#ywgc-main-image").width() < $(".ywgc-main-image").width()) {
                    $(".ywgc-main-image").css("background-color", "#ffe326");
                    $(".ywgc-preview").prepend('<div class="gift-card-too-small">' + ywgc_data.notify_custom_image_small + '</div>');
                }
                else {
                    $(".ywgc-main-image").css("background-color", "");
                }
            }
        }

        //  Remove previous errors shown
        $(".ywgc-picture-error").remove();

        var ext = $(this).val().split('.').pop().toLowerCase();
        if ($.inArray(ext, ['gif', 'png', 'jpg', 'jpeg', 'bmp']) == -1) {
            $("div.gift-card-content-editor.step-appearance").append('<span class="ywgc-picture-error">' +
                ywgc_data.invalid_image_extension + '</span>');
            return;
        }

        if ($(this)[0].files[0].size > ywgc_data.custom_image_max_size * 1024 * 1024) {
            $("div.gift-card-content-editor.step-appearance").append('<span class="ywgc-picture-error">' +
                ywgc_data.invalid_image_size + '</span>');
            return;
        }

        preview_image($(this)[0].files[0]);
    });

    $(document).on('click', '#give-as-present', function (e) {

        e.preventDefault();

        $("div.ywgc-generator").append('<input type="hidden" name="gift_card_enabled" value="1">');
        $("#give-as-present").css("visibility", "hidden");
        $("#ywgc-cancel-gift-card").css("visibility", "visible");

        $("div.ywgc-generator").css('display', 'inherit');
        $("div.ywgc-generator").css('visibility', 'visible');

        $("input[name='ywgc-recipient-email[]']").prop('required', true);
        $("input[name='ywgc-sender-name']").prop('required', true);
    });

    $(document).on('click', '#ywgc-cancel-gift-card', function (e) {
        e.preventDefault();
        $("div.ywgc-generator input[name='gift_card_enabled']").remove();
        $("#give-as-present").css("visibility", "visible");
        $("#ywgc-cancel-gift-card").css("visibility", "hidden");

        $("div.ywgc-generator").css('display', 'none');
        $("input[name='ywgc-recipient-email[]']").prop('required', false);
        $("input[name='ywgc-sender-name']").prop('required', false);
    });

    $(document).on('change', '#ywgc-postdate', function (e) {
        if ($(this).is(':checked')) {
            $("#ywgc-delivery-date").removeClass("hidden");
        }
        else {
            $("#ywgc-delivery-date").addClass("hidden");
        }
    });

    function set_giftcard_value(value) {
        $("div.ywgc-card-amount span.amount").text(value);
    }

    $(document).on('found_variation', function () {
        var variation_price_element = $(".product .entry-summary .price .amount");
        if (variation_price_element.length) {
            set_giftcard_value(variation_price_element.text());
        }
    });


    $(document).on('reset_data', function () {
        set_giftcard_value('');
    });

    function show_edit_gift_cards(element, visible) {
        var container = $(element).closest("div.ywgc-gift-card-content");
        var edit_container = container.find("div.ywgc-gift-card-edit-details");
        var details_container = container.find("div.ywgc-gift-card-details");

        if (visible) {
            //go to edit
            edit_container.removeClass("ywgc-hide");
            edit_container.addClass("ywgc-show");

            details_container.removeClass("ywgc-show");
            details_container.addClass("ywgc-hide");
        }
        else {
            //go to details
            edit_container.removeClass("ywgc-show");
            edit_container.addClass("ywgc-hide");

            details_container.removeClass("ywgc-hide");
            details_container.addClass("ywgc-show");
        }
    }

    $(document).on('click', 'button.ywgc-apply-edit', function (e) {

        var clicked_element = $(this);

        var container = clicked_element.closest("div.ywgc-gift-card-content");

        var sender = container.find('input[name="ywgc-edit-sender"]').val();
        var recipient = container.find('input[name="ywgc-edit-recipient"]').val();
        var message = container.find('textarea[name="ywgc-edit-message"]').val();
        var item_id = container.find('input[name="ywgc-item-id"]').val();

        var gift_card_element = container.find('input[name="ywgc-gift-card-id"]');
        var gift_card_id = gift_card_element.val();

        //  Apply changes, if apply button was clicked
        if (clicked_element.hasClass("apply")) {
            var data = {
                'action'      : 'edit_gift_card',
                'gift_card_id': gift_card_id,
                'item_id'     : item_id,
                'sender'      : sender,
                'recipient'   : recipient,
                'message'     : message
            };

            container.block({
                message   : null,
                overlayCSS: {
                    background: "#fff url(" + ywgc_data.loader + ") no-repeat center",
                    opacity   : .6
                }
            });

            $.post(ywgc_data.ajax_url, data, function (response) {
                if (response.code > 0) {
                    container.find("span.ywgc-sender").text(sender);
                    container.find("span.ywgc-recipient").text(recipient);
                    container.find("span.ywgc-message").text(message);

                    if (response.code == 2) {
                        gift_card_element.val(response.values.new_id);
                    }
                }

                container.unblock();

                //go to details
                show_edit_gift_cards(clicked_element, false);
            });
        }
    });

    $(document).on('click', 'button.ywgc-cancel-edit', function (e) {

        var clicked_element = $(this);

        //go to details
        show_edit_gift_cards(clicked_element, false);
    });

    $(document).on('click', 'button.ywgc-do-edit', function (e) {

        var clicked_element = $(this);
        //go to edit
        show_edit_gift_cards(clicked_element, true);
    });

    $(document).on('click', 'form.gift-cards_form button.gift_card_add_to_cart_button', function (e) {
        $('div.gift-card-content-editor.step-content p.ywgc-filling-error').remove();
        if ($('#ywgc-postdate').is(':checked') && !$.datepicker.parseDate('yy-mm-dd', $('#ywgc-delivery-date').val())) {
            $('div.gift-card-content-editor.step-content').append('<p class="ywgc-filling-error">' + ywgc_data.missing_scheduled_date + '</p>');
            e.preventDefault();
        }
    });

    $(document).on('click', '.ywgc-gift-card-content a.edit-details', function (e) {
        e.preventDefault();
        $(this).addClass('ywgc-hide');
        $('div.ywgc-gift-card-details').toggleClass('ywgc-hide');
    });


    /** Manage the WooCommerce 2.6 changes in the cart template
     * with AJAX
     * @since 1.4.0
     */

    /**
     * Apply the gift card code the same way WooCommerce do for Coupon code
     *
     * @param {JQuery Object} $form The cart form.
     */
    $('form.ywgc-enter-code').submit(function (e) {
        block($(this));

        var $form = $(this);
        var $text_field = $form.find('input[name="coupon_code"]');
        var coupon_code = $text_field.val();

        var data = {
            security    : ywgc_data.apply_coupon_nonce,
            is_gift_card: 1,
            coupon_code : coupon_code
        };

        $.ajax({
            type    : 'POST',
            url     : get_url('apply_coupon'),
            data    : data,
            dataType: 'html',
            success : function (response) {
                show_notice(response);
                $(document.body).trigger('applied_coupon');
            },
            complete: function () {
                unblock($form);
                $text_field.val('');
                update_cart_totals();
            }
        });
    });

    /**
     * Block a node visually for processing.
     *
     * @param {JQuery Object} $node
     */
    var block = function ($node) {
        $node.addClass('processing').block({
            message   : null,
            overlayCSS: {
                background: '#fff',
                opacity   : 0.6
            }
        });
    };

    /**
     * Unblock a node after processing is complete.
     *
     * @param {JQuery Object} $node
     */
    var unblock = function ($node) {
        $node.removeClass('processing').unblock();
    };

    /**
     * Gets a url for a given AJAX endpoint.
     *
     * @param {String} endpoint The AJAX Endpoint
     * @return {String} The URL to use for the request
     */
    var get_url = function (endpoint) {
        return ywgc_data.wc_ajax_url.toString().replace(
            '%%endpoint%%',
            endpoint
        );
    };

    /**
     * Clear previous notices and shows new one above form.
     *
     * @param {Object} The Notice HTML Element in string or object form.
     */
    var show_notice = function (html_element, $target) {
        if (!$target) {
            $target = $('table.shop_table.cart').closest('form');
        }
        $('.woocommerce-error, .woocommerce-message').remove();
        $target.before(html_element);
    };

    /**
     * Update the cart after something has changed.
     */
    function update_cart_totals() {
        block($('div.cart_totals'));

        $.ajax({
            url     : get_url('get_cart_totals'),
            dataType: 'html',
            success : function (response) {
                $('div.cart_totals').replaceWith(response);
            }
        });
    }

});