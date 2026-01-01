<template>
  <div class="rating-page">
    <div class="rating-container">
      <div class="rating-header">
        <h1 class="rating-title">كيف كانت تجربتك؟</h1>
        <p class="rating-subtitle">قيّمي الموظفة {{ staffName }}</p>
      </div>

      <div class="rating-stars">
        <button
          v-for="star in 5"
          :key="star"
          class="star-btn"
          :class="{ active: rating >= star, hover: hoverRating >= star }"
          @click="rating = star"
          @mouseenter="hoverRating = star"
          @mouseleave="hoverRating = 0"
        >
          <CIcon icon="cil-star" />
        </button>
      </div>

      <div class="rating-comment">
        <label for="comment">تعليق (اختياري):</label>
        <textarea
          id="comment"
          v-model="comment"
          rows="4"
          placeholder="شاركينا رأيك..."
          class="comment-input"
        ></textarea>
      </div>

      <div class="rating-actions">
        <button class="submit-btn" @click="submitRating" :disabled="!rating || submitting">
          {{ submitting ? 'جاري الإرسال...' : 'إرسال التقييم' }}
        </button>
      </div>

      <div v-if="submitted" class="rating-success">
        <p>شكراً لك! تم إرسال تقييمك بنجاح.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { CIcon } from '@coreui/icons-vue';
import api from '@/utils/api';

const route = useRoute();
const rating = ref(0);
const hoverRating = ref(0);
const comment = ref('');
const submitting = ref(false);
const submitted = ref(false);
const staffName = ref('Staff');
const staffId = ref(null);
const bookingId = ref(null);
const customerId = ref(null);

const loadBookingInfo = async () => {
  const bookingIdParam = route.query.booking_id || route.params.booking_id;
  if (!bookingIdParam) {
    console.error('No booking_id provided');
    return;
  }

  bookingId.value = parseInt(bookingIdParam);

  try {
    const response = await api.get(`/bookings/${bookingId.value}`);
    const booking = response.data?.data || response.data;
    
    if (booking) {
      staffId.value = booking.staff_id;
      customerId.value = booking.customer_id;

      // Load staff name
      if (staffId.value) {
        try {
          const staffRes = await api.get(`/staff/${staffId.value}`);
          const staff = staffRes.data?.data || staffRes.data;
          if (staff) {
            staffName.value = staff.name || 'Staff';
          }
        } catch (e) {
          console.error('Error loading staff:', e);
        }
      }
    }
  } catch (error) {
    console.error('Error loading booking:', error);
  }
};

const submitRating = async () => {
  if (!rating.value || !staffId.value) {
    alert('Please select a rating');
    return;
  }

  submitting.value = true;

  try {
    await api.post('/ratings', {
      staff_id: staffId.value,
      customer_id: customerId.value,
      booking_id: bookingId.value,
      rating: rating.value,
      comment: comment.value,
    });

    submitted.value = true;
    
    // Redirect after 2 seconds
    setTimeout(() => {
      window.close();
    }, 2000);
  } catch (error) {
    console.error('Error submitting rating:', error);
    alert('Error submitting rating. Please try again.');
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  loadBookingInfo();
});
</script>

<style scoped>
.rating-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.rating-container {
  background: white;
  border-radius: 20px;
  padding: 40px;
  max-width: 500px;
  width: 100%;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  text-align: center;
}

.rating-header {
  margin-bottom: 30px;
}

.rating-title {
  font-size: 2rem;
  color: #333;
  margin-bottom: 10px;
}

.rating-subtitle {
  font-size: 1.2rem;
  color: #666;
}

.rating-stars {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin: 30px 0;
}

.star-btn {
  background: none;
  border: none;
  font-size: 3rem;
  cursor: pointer;
  transition: transform 0.2s;
  filter: grayscale(100%);
  opacity: 0.5;
}

.star-btn:hover {
  transform: scale(1.2);
}

.star-btn.active,
.star-btn.hover {
  filter: grayscale(0%);
  opacity: 1;
}

.rating-comment {
  margin: 30px 0;
  text-align: right;
}

.rating-comment label {
  display: block;
  margin-bottom: 10px;
  color: #333;
  font-weight: 500;
}

.comment-input {
  width: 100%;
  padding: 12px;
  border: 2px solid #ddd;
  border-radius: 8px;
  font-size: 1rem;
  font-family: inherit;
  resize: vertical;
}

.comment-input:focus {
  outline: none;
  border-color: #667eea;
}

.rating-actions {
  margin-top: 30px;
}

.submit-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 15px 40px;
  font-size: 1.1rem;
  border-radius: 25px;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  font-weight: 600;
}

.submit-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.rating-success {
  margin-top: 20px;
  padding: 15px;
  background: #d4edda;
  border: 1px solid #c3e6cb;
  border-radius: 8px;
  color: #155724;
}

.rating-success p {
  margin: 0;
  font-size: 1.1rem;
}
</style>
