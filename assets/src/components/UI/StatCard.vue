<template>
  <div 
    class="stat-card" 
    :class="[
      `stat-card-${color}`,
      { 'stat-card-clickable': clickable, 'stat-card-active': active }
    ]"
    @click="clickable && $emit('click')"
  >
    
    <div class="stat-card-icon" v-if="$slots.icon">
      <slot name="icon"></slot>
    </div>
    <!-- Logo Background - Letter A -->
    <div class="stat-card-logo-bg">
      <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
        <text 
          x="50%" 
          y="50%" 
          font-family="'Arial Black', 'Helvetica Neue', Arial, sans-serif" 
          font-size="220" 
          font-weight="900" 
          fill="rgba(255, 255, 255, 0.3)" 
          text-anchor="middle" 
          dominant-baseline="central"
          letter-spacing="-18"
          stroke="rgba(255, 255, 255, 0.2)"
          stroke-width="3"
          paint-order="stroke fill"
        >
          A
        </text>
      </svg>
    </div>
    
    <div class="stat-card-icon" v-if="$slots.icon">
      <slot name="icon"></slot>
    </div>
    <div class="stat-card-content">
      <div class="stat-card-label">{{ label }}</div>
      <div class="stat-card-value">{{ value }}</div>
      <div class="stat-card-badge" v-if="badge">
        <span :class="`badge-${badgeVariant}`">{{ badge }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  badge: {
    type: String,
    default: ''
  },
  badgeVariant: {
    type: String,
    default: 'info'
  },
  color: {
    type: String,
    default: 'blue'
  },
  clickable: {
    type: Boolean,
    default: false
  },
  active: {
    type: Boolean,
    default: false
  }
});

defineEmits(['click']);
</script>

<style scoped>
.stat-card {
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  box-shadow: var(--shadow-md);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  border: none;
  background: var(--bg-primary);
}

.stat-card-clickable {
  cursor: pointer;
}

.stat-card-clickable:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.stat-card-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  position: relative;
  z-index: 1;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.stat-card-icon :deep(svg) {
  width: 28px;
  height: 28px;
  color: white;
}

.stat-card-content {
  flex: 1;
  min-width: 0;
  position: relative;
  z-index: 1;
}

.stat-card-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}

.stat-card-value {
  font-size: 2rem;
  font-weight: 800;
  color: white;
  line-height: 1.2;
  margin-bottom: 0.5rem;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.stat-card-badge {
  font-size: 0.75rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  color: rgba(255, 255, 255, 0.95);
}

/* Color Variants */
.stat-card-blue {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

.stat-card-green {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.stat-card-yellow {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.stat-card-purple {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
  color: white;
}

.stat-card-pink {
  background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
  color: white;
}

.stat-card-teal {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: white;
}

.stat-card-indigo {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: white;
}

.stat-card-orange {
  background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
  color: white;
}

.stat-card-gold {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.85) 100%);
  color: white;
}


/* Logo Background */
.stat-card-logo-bg {
  position: absolute;
  top: 0;
  right: 0;
  width: 220px;
  height: 220px;
  opacity: 0.35;
  pointer-events: none;
  overflow: visible;
  z-index: 0;
  transform: translate(10%, -5%);
}

.stat-card-logo-bg svg {
  width: 100%;
  height: 100%;
  filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.15));
}

/* Ensure all stat cards use gold color by default */
.stat-card-blue,
.stat-card-green,
.stat-card-yellow,
.stat-card-purple,
.stat-card-pink,
.stat-card-teal,
.stat-card-indigo,
.stat-card-orange {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.85) 100%);
  color: white;
}

/* Badge Variants */
.badge-info {
  background: rgba(255, 255, 255, 0.25);
  color: white;
  padding: 0.125rem 0.5rem;
  border-radius: 999px;
  font-size: 0.7rem;
}

.badge-success {
  background: rgba(16, 185, 129, 0.3);
  color: white;
  padding: 0.125rem 0.5rem;
  border-radius: 999px;
  font-size: 0.7rem;
}

.badge-warning {
  background: rgba(245, 158, 11, 0.3);
  color: white;
  padding: 0.125rem 0.5rem;
  border-radius: 999px;
  font-size: 0.7rem;
}

.badge-danger {
  background: rgba(239, 68, 68, 0.3);
  color: white;
  padding: 0.125rem 0.5rem;
  border-radius: 999px;
  font-size: 0.7rem;
}
</style>
