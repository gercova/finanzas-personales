<template>
  <div>
    <!-- Dashboard content is rendered by the existing PHP dashboard.php shell.
         This view is a Vue-managed route that simply activates the nav state.
         The actual dashboard widgets (stat cards, calendar, tables) stay in PHP
         and are shown inside the main container below the SPA nav. -->
    <div id="spa-dashboard-mount">
      <!-- PHP dashboard content loads here via the shell page -->
    </div>
  </div>
</template>

<script setup>
// Activate dashboard nav item when this view mounts
import { onMounted } from 'vue'
onMounted(() => {
  document.querySelectorAll('.navbar-nav li').forEach(li => li.classList.remove('active'))
  document.getElementById('navDashboard')?.classList.add('active')
})
</script>
