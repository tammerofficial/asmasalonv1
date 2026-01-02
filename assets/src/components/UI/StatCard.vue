<template>
  <div 
    class="stat-card" 
    :class="[
      `stat-card-${color}`,
      { 'stat-card-clickable': clickable, 'stat-card-active': active }
    ]"
    @click="clickable && $emit('click')"
  >
    
    <!-- Animated Chart Background -->
    <div class="stat-card-chart-bg">
      <svg class="chart-svg" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <linearGradient id="lineGradient" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color: rgba(255, 255, 255, 0.5); stop-opacity: 1" />
            <stop offset="50%" style="stop-color: rgba(255, 255, 255, 0.3); stop-opacity: 1" />
            <stop offset="100%" style="stop-color: rgba(255, 255, 255, 0.1); stop-opacity: 1" />
          </linearGradient>
          <linearGradient id="areaGradient" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" style="stop-color: rgba(255, 255, 255, 0.15); stop-opacity: 1" />
            <stop offset="100%" style="stop-color: rgba(255, 255, 255, 0); stop-opacity: 1" />
          </linearGradient>
        </defs>
        
        <!-- Area Chart Fill -->
        <path 
          class="chart-area"
          d="M 20 160 Q 50 120, 80 140 T 140 100 T 180 80 L 180 200 L 20 200 Z"
          fill="url(#areaGradient)"
        />
        
        <!-- Main Line Chart -->
        <path 
          class="chart-line"
          d="M 20 160 Q 50 120, 80 140 T 140 100 T 180 80"
          fill="none"
          stroke="url(#lineGradient)"
          stroke-width="3"
          stroke-linecap="round"
        />
        
        <!-- Secondary Line -->
        <path 
          class="chart-line chart-line-2"
          d="M 20 180 Q 50 140, 80 160 T 140 120 T 180 100"
          fill="none"
          stroke="url(#lineGradient)"
          stroke-width="2"
          stroke-linecap="round"
          opacity="0.6"
        />
        
        <!-- Tertiary Line -->
        <path 
          class="chart-line chart-line-3"
          d="M 10 150 Q 40 110, 70 130 T 130 90 T 170 70"
          fill="none"
          stroke="rgba(255, 255, 255, 0.25)"
          stroke-width="2"
          stroke-linecap="round"
          opacity="0.4"
        />
        
        <!-- Animated Data Points -->
        <circle class="chart-circle" cx="50" cy="120" r="4" fill="rgba(255, 255, 255, 0.7)" />
        <circle class="chart-circle chart-circle-2" cx="100" cy="100" r="4" fill="rgba(255, 255, 255, 0.7)" />
        <circle class="chart-circle chart-circle-3" cx="150" cy="80" r="4" fill="rgba(255, 255, 255, 0.7)" />
        <circle class="chart-circle chart-circle-4" cx="80" cy="140" r="3" fill="rgba(255, 255, 255, 0.5)" />
        <circle class="chart-circle chart-circle-5" cx="140" cy="100" r="3" fill="rgba(255, 255, 255, 0.5)" />
        
        <!-- Animated Wave Bottom -->
        <path 
          class="chart-wave"
          d="M 0 190 Q 50 170, 100 190 T 200 190"
          fill="none"
          stroke="rgba(255, 255, 255, 0.25)"
          stroke-width="2"
        />
        
        <!-- Floating Particles -->
        <circle class="chart-particle" cx="30" cy="50" r="2" fill="rgba(255, 255, 255, 0.3)" />
        <circle class="chart-particle chart-particle-2" cx="120" cy="40" r="2" fill="rgba(255, 255, 255, 0.3)" />
        <circle class="chart-particle chart-particle-3" cx="170" cy="60" r="2" fill="rgba(255, 255, 255, 0.3)" />
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
  background: linear-gradient(135deg, var(--asmaa-info) 0%, #2563eb 100%);
  color: white;
}

.stat-card-green {
  background: linear-gradient(135deg, var(--asmaa-success) 0%, #059669 100%);
  color: white;
}

.stat-card-yellow {
  background: linear-gradient(135deg, var(--asmaa-warning) 0%, #d97706 100%);
  color: white;
}

.stat-card-purple {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-dark) 100%);
  color: white;
}

.stat-card-pink {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-dark) 100%);
  color: white;
}

.stat-card-teal {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: white;
}

.stat-card-indigo {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-dark) 100%);
  color: white;
}

.stat-card-orange {
  background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
  color: white;
}

.stat-card-gold {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-dark) 100%);
  color: white;
}


/* Animated Chart Background */
.stat-card-chart-bg {
  position: absolute;
  top: 0;
  right: 0;
  width: 100%;
  height: 100%;
  opacity: 0.5;
  pointer-events: none;
  overflow: hidden;
  z-index: 0;
  transform: translate(10%, -8%) scale(1.15);
}

.chart-svg {
  width: 100%;
  height: 100%;
  filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.1));
}

/* Animated Area Fill */
.chart-area {
  animation: fadeArea 4s ease-in-out infinite;
  opacity: 0;
}

@keyframes fadeArea {
  0%, 100% {
    opacity: 0;
  }
  50% {
    opacity: 1;
  }
}

/* Animated Line Chart */
.chart-line {
  animation: drawLine 3.5s ease-in-out infinite;
  stroke-dasharray: 350;
  stroke-dashoffset: 350;
}

.chart-line-2 {
  animation: drawLine 3.5s ease-in-out infinite 0.7s;
  stroke-dasharray: 350;
  stroke-dashoffset: 350;
}

.chart-line-3 {
  animation: drawLine 3.5s ease-in-out infinite 1.2s;
  stroke-dasharray: 350;
  stroke-dashoffset: 350;
}

@keyframes drawLine {
  0% {
    stroke-dashoffset: 350;
    opacity: 0;
  }
  30% {
    opacity: 1;
  }
  70% {
    opacity: 1;
  }
  100% {
    stroke-dashoffset: 0;
    opacity: 0.7;
  }
}

/* Animated Circles - Data Points */
.chart-circle {
  animation: pulseCircle 2.5s ease-in-out infinite;
  transform-origin: center;
}

.chart-circle-2 {
  animation: pulseCircle 2.5s ease-in-out infinite 0.4s;
}

.chart-circle-3 {
  animation: pulseCircle 2.5s ease-in-out infinite 0.8s;
}

.chart-circle-4 {
  animation: pulseCircle 2.5s ease-in-out infinite 0.2s;
}

.chart-circle-5 {
  animation: pulseCircle 2.5s ease-in-out infinite 0.6s;
}

@keyframes pulseCircle {
  0%, 100% {
    r: 3;
    opacity: 0.5;
  }
  50% {
    r: 5;
    opacity: 0.9;
  }
}

/* Animated Wave */
.chart-wave {
  animation: waveMove 5s ease-in-out infinite;
  stroke-dasharray: 250;
  stroke-dashoffset: 0;
}

@keyframes waveMove {
  0% {
    stroke-dashoffset: 0;
    opacity: 0.2;
  }
  50% {
    stroke-dashoffset: 125;
    opacity: 0.4;
  }
  100% {
    stroke-dashoffset: 250;
    opacity: 0.2;
  }
}

/* Floating Particles */
.chart-particle {
  animation: floatParticle 6s ease-in-out infinite;
  opacity: 0;
}

.chart-particle-2 {
  animation: floatParticle 6s ease-in-out infinite 2s;
}

.chart-particle-3 {
  animation: floatParticle 6s ease-in-out infinite 4s;
}

@keyframes floatParticle {
  0%, 100% {
    transform: translateY(0) translateX(0);
    opacity: 0;
  }
  25% {
    opacity: 0.5;
  }
  50% {
    transform: translateY(-20px) translateX(10px);
    opacity: 0.8;
  }
  75% {
    opacity: 0.5;
  }
}

/* Ensuring font consistency */
.stat-card-label, .stat-card-value, .stat-card-badge {
  font-family: var(--font-family-body);
}
</style>
