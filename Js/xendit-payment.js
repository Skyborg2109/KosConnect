/**
 * Xendit Payment Integration Script
 * Simple JavaScript untuk handle Xendit payment
 */

class XenditPayment {
    constructor() {
        this.processing = false;
    }

    /**
     * Process payment with Xendit
     */
    async processPayment(bookingId, amount, paymentMethod = 'Xendit Payment') {
        if (this.processing) {
            return;
        }

        try {
            this.processing = true;

            // Show loading
            Swal.fire({
                title: 'Membuat Invoice...',
                html: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Create form data
            const formData = new FormData();
            formData.append('id_booking', bookingId);
            formData.append('jumlah', amount);
            formData.append('metode_pembayaran', paymentMethod);
            formData.append('payment_method', 'xendit');

            // Send request
            const response = await fetch('process_payment.php', {
                method: 'POST',
                body: formData
            });

            // Get response text first for debugging
            const responseText = await response.text();
            console.log('Server response:', responseText);

            // Try to parse as JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                // If JSON parse fails, show the actual response
                console.error('Failed to parse JSON. Server response:', responseText);
                throw new Error('Server error: ' + responseText.substring(0, 300));
            }

            if (result.success && result.invoice_url) {
                // Close loading
                Swal.close();

                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Invoice Berhasil Dibuat!',
                    html: 'Anda akan diarahkan ke halaman pembayaran Xendit',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Redirect to Xendit invoice page
                    window.location.href = result.invoice_url;
                });
            } else {
                throw new Error(result.error || 'Gagal membuat invoice');
            }

        } catch (error) {
            console.error('Payment error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Gagal Membuat Invoice',
                html: `<div style="text-align: left; max-height: 300px; overflow-y: auto;"><pre>${error.message}</pre></div>`,
                confirmButtonText: 'OK',
                width: '600px'
            });
        } finally {
            this.processing = false;
        }
    }
}

// Initialize
window.xenditPayment = new XenditPayment();
