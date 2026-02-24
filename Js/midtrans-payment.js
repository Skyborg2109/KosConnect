/**
 * Midtrans Payment Integration
 * Handle Snap payment popup and callbacks
 */

class MidtransPayment {
    constructor(clientKey) {
        this.clientKey = clientKey;
        this.snapLoaded = false;
        this.loadSnapScript();
    }

    /**
     * Load Midtrans Snap.js script
     */
    loadSnapScript() {
        if (document.getElementById('midtrans-snap-script')) {
            this.snapLoaded = true;
            return;
        }

        const script = document.createElement('script');
        script.id = 'midtrans-snap-script';
        script.src = 'https://app.midtrans.com/snap/snap.js';
        script.setAttribute('data-client-key', this.clientKey);
        script.onload = () => {
            this.snapLoaded = true;
            console.log('Midtrans Snap.js loaded successfully');
        };
        script.onerror = () => {
            console.error('Failed to load Midtrans Snap.js');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Gagal memuat payment gateway. Silakan refresh halaman.',
            });
        };
        document.head.appendChild(script);
    }

    /**
     * Process payment with Midtrans
     */
    async processPayment(bookingId, amount, paymentMethod) {
        try {
            // Show loading
            Swal.fire({
                title: 'Memproses Pembayaran...',
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
            formData.append('payment_method', 'midtrans');

            // Send request to create transaction
            const response = await fetch('process_payment.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success && result.snap_token) {
                // Close loading
                Swal.close();

                // Wait for Snap to load
                await this.waitForSnap();

                // Show Snap payment popup
                window.snap.pay(result.snap_token, {
                    onSuccess: (result) => {
                        this.handlePaymentSuccess(result);
                    },
                    onPending: (result) => {
                        this.handlePaymentPending(result);
                    },
                    onError: (result) => {
                        this.handlePaymentError(result);
                    },
                    onClose: () => {
                        this.handlePaymentClose();
                    }
                });
            } else {
                throw new Error(result.error || 'Gagal membuat transaksi pembayaran');
            }

        } catch (error) {
            console.error('Payment error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memproses Pembayaran',
                text: error.message || 'Terjadi kesalahan saat memproses pembayaran',
                confirmButtonText: 'OK'
            });
        }
    }

    /**
     * Wait for Snap.js to load
     */
    waitForSnap() {
        return new Promise((resolve, reject) => {
            const maxAttempts = 50;
            let attempts = 0;

            const checkSnap = setInterval(() => {
                attempts++;

                if (window.snap) {
                    clearInterval(checkSnap);
                    resolve();
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkSnap);
                    reject(new Error('Timeout waiting for Snap.js'));
                }
            }, 100);
        });
    }

    /**
     * Handle successful payment
     */
    handlePaymentSuccess(result) {
        console.log('Payment success:', result);

        Swal.fire({
            icon: 'success',
            title: 'Pembayaran Berhasil!',
            html: `
                <p>Pembayaran Anda telah berhasil diproses.</p>
                <p class="text-sm text-gray-600 mt-2">Order ID: ${result.order_id}</p>
            `,
            confirmButtonText: 'Lihat Booking Saya',
            allowOutsideClick: false
        }).then(() => {
            window.location.href = 'user_dashboard.php#riwayat';
        });
    }

    /**
     * Handle pending payment
     */
    handlePaymentPending(result) {
        console.log('Payment pending:', result);

        let message = 'Pembayaran Anda sedang diproses. Silakan selesaikan pembayaran sesuai instruksi.';

        // Add specific instructions based on payment type
        if (result.payment_type === 'bank_transfer' && result.va_numbers) {
            const vaNumber = result.va_numbers[0].va_number;
            const bank = result.va_numbers[0].bank.toUpperCase();
            message += `<br><br><strong>Virtual Account ${bank}:</strong><br><code style="font-size: 18px; background: #f3f4f6; padding: 8px; border-radius: 4px;">${vaNumber}</code>`;
        } else if (result.payment_type === 'qris') {
            message += '<br><br>Scan QR Code yang ditampilkan untuk menyelesaikan pembayaran.';
        }

        Swal.fire({
            icon: 'info',
            title: 'Menunggu Pembayaran',
            html: message,
            confirmButtonText: 'OK',
            allowOutsideClick: false
        }).then(() => {
            window.location.href = 'user_dashboard.php#riwayat';
        });
    }

    /**
     * Handle payment error
     */
    handlePaymentError(result) {
        console.error('Payment error:', result);

        Swal.fire({
            icon: 'error',
            title: 'Pembayaran Gagal',
            text: 'Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.',
            confirmButtonText: 'OK'
        });
    }

    /**
     * Handle payment popup closed
     */
    handlePaymentClose() {
        console.log('Payment popup closed');

        Swal.fire({
            icon: 'warning',
            title: 'Pembayaran Dibatalkan',
            text: 'Anda menutup halaman pembayaran. Silakan coba lagi jika ingin melanjutkan.',
            confirmButtonText: 'OK'
        });
    }

    /**
     * Check payment status
     */
    async checkPaymentStatus(bookingId) {
        try {
            const response = await fetch(`../api/check_payment_status.php?booking_id=${bookingId}`);
            const result = await response.json();

            if (result.success) {
                return result.data;
            } else {
                throw new Error(result.error || 'Failed to check payment status');
            }
        } catch (error) {
            console.error('Check payment status error:', error);
            return null;
        }
    }
}

// Export for use in other scripts
window.MidtransPayment = MidtransPayment;
