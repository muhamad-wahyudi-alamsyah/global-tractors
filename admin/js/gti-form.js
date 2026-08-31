/**
 * GTI Form JavaScript
 * 
 * Multi-step form handler
 */

(function($) {
    'use strict';

    var GTI_Form = {
        currentStep: 1,
        totalSteps: 5,

        init: function() {
            this.bindEvents();
            this.initMultiStep();
        },

        bindEvents: function() {
            // Next step
            $(document).on('click', '.gti-btn-next', this.nextStep);
            
            // Previous step
            $(document).on('click', '.gti-btn-prev', this.prevStep);
            
            // Go to step
            $(document).on('click', '.gti-step', this.goToStep);
            
            // Form submit
            $(document).on('submit', '.gti-form', this.handleSubmit);
            
            // Image upload
            $(document).on('click', '.gti-upload-btn', this.handleImageUpload);
            
            // Remove image
            $(document).on('click', '.gti-remove-image', this.removeImage);
            
            // Add item (for quotation items)
            $(document).on('click', '.gti-add-item', this.addItem);
            
            // Remove item
            $(document).on('click', '.gti-remove-item', this.removeItem);
        },

        initMultiStep: function() {
            var $form = $('.gti-multi-step-form');
            if (!$form.length) return;
            
            this.totalSteps = $form.data('steps') || 5;
            this.showStep(1);
        },

        showStep: function(step) {
            // Hide all steps
            $('.gti-step-content').hide();
            
            // Show target step
            $('.gti-step-content[data-step="' + step + '"]').show();
            
            // Update stepper UI
            $('.gti-step').each(function() {
                var stepNum = $(this).data('step');
                $(this).removeClass('active completed');
                
                if (stepNum < step) {
                    $(this).addClass('completed');
                } else if (stepNum === step) {
                    $(this).addClass('active');
                }
            });
            
            // Update buttons
            $('.gti-btn-prev').toggle(step > 1);
            $('.gti-btn-next').toggle(step < this.totalSteps);
            $('.gti-btn-submit').toggle(step === this.totalSteps);
            
            this.currentStep = step;
        },

        nextStep: function(e) {
            e.preventDefault();
            
            var $form = $('.gti-multi-step-form');
            
            // Validate current step
            if (!GTI_Form.validateStep(GTI_Form.currentStep)) {
                return;
            }
            
            // Save current step data
            GTI_Form.saveStepData(GTI_Form.currentStep);
            
            // Move to next step
            if (GTI_Form.currentStep < GTI_Form.totalSteps) {
                GTI_Form.showStep(GTI_Form.currentStep + 1);
            }
        },

        prevStep: function(e) {
            e.preventDefault();
            
            if (GTI_Form.currentStep > 1) {
                GTI_Form.showStep(GTI_Form.currentStep - 1);
            }
        },

        goToStep: function(e) {
            e.preventDefault();
            
            var $step = $(this);
            var stepNum = $step.data('step');
            
            // Only allow going to completed steps or next step
            if (stepNum <= GTI_Form.currentStep) {
                GTI_Form.showStep(stepNum);
            }
        },

        validateStep: function(step) {
            var $stepContent = $('.gti-step-content[data-step="' + step + '"]');
            var isValid = true;
            
            // Check required fields
            $stepContent.find('.gti-form-control[required]').each(function() {
                var $field = $(this);
                
                if (!$field.val()) {
                    isValid = false;
                    $field.addClass('error');
                    $field.after('<span class="gti-form-error">This field is required</span>');
                } else {
                    $field.removeClass('error');
                    $field.next('.gti-form-error').remove();
                }
            });
            
            // Check email fields
            $stepContent.find('input[type="email"]').each(function() {
                var $field = $(this);
                var email = $field.val();
                
                if (email && !GTI_Form.isValidEmail(email)) {
                    isValid = false;
                    $field.addClass('error');
                    $field.after('<span class="gti-form-error">Please enter a valid email</span>');
                }
            });
            
            if (!isValid) {
                GTI_Admin.showToast('Please fill in all required fields', 'error');
            }
            
            return isValid;
        },

        saveStepData: function(step) {
            var $stepContent = $('.gti-step-content[data-step="' + step + '"]');
            var formData = {};
            
            $stepContent.find('.gti-form-control').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();
                
                if (name) {
                    formData[name] = value;
                }
            });
            
            // Store in hidden field
            var allData = JSON.parse($('#gti-form-data').val() || '{}');
            allData['step' + step] = formData;
            $('#gti-form-data').val(JSON.stringify(allData));
        },

        handleSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('.gti-btn-submit');
            
            // Validate all steps
            if (!GTI_Form.validateStep(GTI_Form.currentStep)) {
                return;
            }
            
            // Save last step data
            GTI_Form.saveStepData(GTI_Form.currentStep);
            
            // Collect all form data
            var formData = new FormData($form[0]);
            
            // Add nonce
            formData.append('nonce', gtiAjax.nonce);
            
            $.ajax({
                url: gtiAjax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $submitBtn.prop('disabled', true).html('<span class="gti-spinner"></span> Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        GTI_Admin.showToast(response.data.message || 'Saved successfully', 'success');
                        
                        // Redirect if needed
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1000);
                        }
                    } else {
                        GTI_Admin.showToast(response.data.message || 'Save failed', 'error');
                        $submitBtn.prop('disabled', false).text('Save');
                    }
                },
                error: function() {
                    GTI_Admin.showToast('An error occurred', 'error');
                    $submitBtn.prop('disabled', false).text('Save');
                }
            });
        },

        handleImageUpload: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $container = $btn.closest('.gti-image-upload');
            var $input = $container.find('input[type="file"]');
            
            $input.click();
        },

        removeImage: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $container = $btn.closest('.gti-image-upload');
            
            $container.find('input[type="file"]').val('');
            $container.find('.gti-image-preview').html('');
            $container.find('input[type="hidden"]').val('');
        },

        addItem: function(e) {
            e.preventDefault();
            
            var $table = $($(this).data('target'));
            var template = $table.find('tr:last').clone();
            
            // Clear values
            template.find('input').val('');
            template.find('select').val('');
            
            // Update indexes
            var newIndex = $table.find('tr').length - 1;
            template.find('[name]').each(function() {
                var name = $(this).attr('name').replace(/\[\d+\]/, '[' + newIndex + ']');
                $(this).attr('name', name);
            });
            
            $table.find('tbody').append(template);
            
            // Recalculate totals
            this.recalculateTotals();
        },

        removeItem: function(e) {
            e.preventDefault();
            
            var $row = $(this).closest('tr');
            var $table = $row.closest('table');
            
            if ($table.find('tr').length > 2) { // Keep header and at least one row
                $row.remove();
                this.recalculateTotals();
            }
        },

        recalculateTotals: function() {
            var subtotal = 0;
            
            $('table.gti-items-table tbody tr').each(function() {
                var qty = parseInt($(this).find('.item-qty').val()) || 0;
                var price = parseFloat($(this).find('.item-price').val()) || 0;
                var total = qty * price;
                
                $(this).find('.item-total').val(total);
                subtotal += total;
            });
            
            // Update subtotal
            $('.gti-subtotal').text(GTI_Admin.formatCurrency(subtotal));
            
            // Calculate discount
            var discountType = $('select[name="discount_type"]').val();
            var discountValue = parseFloat($('input[name="discount"]').val()) || 0;
            var discount = discountType === 'percentage' ? (subtotal * discountValue / 100) : discountValue;
            
            // Calculate tax
            var taxRate = parseFloat($('input[name="tax_rate"]').val()) || 11;
            var taxAmount = (subtotal - discount) * (taxRate / 100);
            
            // Calculate total
            var total = subtotal - discount + taxAmount;
            
            // Update UI
            $('.gti-discount').text('-' + GTI_Admin.formatCurrency(discount));
            $('.gti-tax').text(GTI_Admin.formatCurrency(taxAmount));
            $('.gti-total').text(GTI_Admin.formatCurrency(total));
            
            // Update hidden inputs
            $('input[name="subtotal"]').val(subtotal);
            $('input[name="tax_amount"]').val(taxAmount);
            $('input[name="total"]').val(total);
        },

        isValidEmail: function(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        GTI_Form.init();
    });

    // Make available globally
    window.GTI_Form = GTI_Form;

})(jQuery);