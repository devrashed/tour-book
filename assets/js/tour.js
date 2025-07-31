
   
    /* Modal Booking Form */
            
            
            jQuery(document).ready(function($) {
                // Open modal when tour title is clicked
                jQuery('.tour-booking-link').on('click', function(e) {
                    e.preventDefault();
                    var tourId = jQuery(this).data('tour-id');
                    var tourTitle = jQuery(this).data('tour-title');
                    
                    jQuery('#tour-id').val(tourId);
                    jQuery('#modal-tour-title').text('Book: ' + tourTitle);
                    jQuery('#booking-modal').show();
                });
                
                // Close modal
                jQuery('.close-modal, .cancel-btn').on('click', function() {
                    jQuery('#booking-modal').hide();
                    jQuery('#booking-form')[0].reset();
                    jQuery('#booking-result').hide();
                });
                
                // Close modal when clicking outside
                jQuery(window).on('click', function(e) {
                    if (e.target.id === 'booking-modal') {
                        jQuery('#booking-modal').hide();
                        jQuery('#booking-form')[0].reset();
                        $jQuery('#booking-result').hide();
                    }
                });
                
                // Handle form submission
                jQuery('#booking-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    var formData = {
                        action: 'submit_tour_booking',
                        tour_id: jQuery('#tour-id').val(),
                        name: jQuery('#customer-name').val(),
                        email: jQuery('#customer-email').val(),
                        phone: jQuery('#customer-phone').val(),
                        message: jQuery('#customer-message').val(),
                        nonce: tour_ajax_obj.nonce
                    };
                    
                    jQuery.ajax({
                        url: tour_ajax_obj.ajax_url,
                        type: 'POST',
                        data: formData,
                        beforeSend: function() {
                            jQuery('#booking-form button[type="submit"]').prop('disabled', true).text('Submitting...');
                        },
                        success: function(response) {
                            if (response.success) {
                                jQuery('#booking-result').removeClass('error').addClass('success').text(response.data.message).show();
                                jQuery('#booking-form')[0].reset();
                                setTimeout(function() {
                                    jQuery('#booking-modal').hide();
                                    jQuery('#booking-result').hide();
                                }, 2000);
                            } else {
                                jQuery('#booking-result').removeClass('success').addClass('error').text(response.data.message).show();
                            }
                        },
                        error: function() {
                            jQuery('#booking-result').removeClass('success').addClass('error').text('An error occurred. Please try again.').show();
                        },
                        complete: function() {
                            $('#booking-form button[type="submit"]').prop('disabled', false).text('Submit Booking');
                        }
                    });
                });
            });