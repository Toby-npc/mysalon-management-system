document.addEventListener('DOMContentLoaded', () => {
    // 1. Time Slot State Mechanics
    const timeSlots = document.querySelectorAll('.time');
    let selectedTimeSlot = '';

    timeSlots.forEach(slot => {
        slot.addEventListener('click', () => {
            timeSlots.forEach(s => s.classList.remove('selected'));
            slot.classList.add('selected');
            selectedTimeSlot = slot.getAttribute('data-time');
        });
    });

    // 2. Multi-Page Data Dispatch Form Listener
    const appointmentForm = document.getElementById('appointmentForm');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const name = document.getElementById('fullName').value;
            const phone = document.getElementById('phoneNumber').value;
            const service = document.getElementById('serviceSelect').value;
            const date = document.getElementById('bookingDate').value;

            if (!selectedTimeSlot) {
                alert('Please pick a premium time slot selection entry.');
                return;
            }

            const booking = {
                name: name,
                phone: phone,
                service: service,
                date: date,
                time: selectedTimeSlot
            };

            // Write payload object globally across local pages
            localStorage.setItem('salonBooking', JSON.stringify(booking));
            alert('Your appointment space has been confirmed!');
            
            // Redirect smoothly to the record summary review page
            window.location.href = 'my-booking.html';
        });
    }

    // 3. Independent Execution Guard to Read Persistent Storage Elements
    if (document.getElementById('bookingsContainer')) {
        displayBooking();
    }
});

function displayBooking() {
    const data = localStorage.getItem('salonBooking');
    const container = document.getElementById('bookingsContainer');

    if (!container) return;

    if (!data) {
        container.innerHTML = `<p style="text-align: center; color: #8a7c7d; padding: 40px 0;">No active bookings found at this moment.</p>`;
        return;
    }

    const booking = JSON.parse(data);

    container.innerHTML = `
        <div class="booking-card">
            <h3>Confirmed Session - ${booking.service} ✨</h3>
            <p><strong>Client Representative:</strong> ${booking.name}</p>
            <p><strong>Contact Line:</strong> ${booking.phone}</p>
            <p><strong>Scheduled Date:</strong> ${booking.date}</p>
            <p><strong>Allocated Window Slot:</strong> ${booking.time}</p>
            <p><strong>Booking Status:</strong> Confirmed</p>
            <button class="modify-btn" onclick="modifyBooking()">Modify Details</button>
            <button class="cancel-btn" onclick="cancelBooking()">Cancel Allocation</button>
        </div>
    `;
}

function modifyBooking() {
    // Route customer back to application parameter screen
    window.location.href = 'MySalon(BOOKING).html';
}

function cancelBooking() {
    if (confirm('Are you certain you wish to cancel this styling allocation session?')) {
        localStorage.removeItem('salonBooking');
        displayBooking();
        alert('Booking cancelled successfully.');
    }
}
        displayBooking();

    }
);
