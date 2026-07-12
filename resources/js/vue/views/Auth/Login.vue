<template>
  <Head title="MTC - Sign In">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet" />
  </Head>
  <div dir="ltr" class="bg-background min-h-screen flex text-on-surface antialiased selection:bg-primary-container selection:text-on-primary-container">
    
<!-- Split Screen Layout -->
<div class="flex w-full min-h-screen relative">
<!-- Left Side: Branding / Imagery (Hidden on Mobile) -->
<div class="hidden lg:flex w-1/2 relative bg-primary overflow-hidden items-center justify-center flex-col p-margin-desktop">
<!-- Background Image with Teal Overlay -->
<div class="absolute inset-0 z-0">
<div class="w-full h-full bg-cover bg-center mix-blend-overlay opacity-40" data-alt="A professional medical courier in uniform carefully loading a temperature-controlled biometric transport box into a modern delivery vehicle. The scene is brightly lit with cool, clinical lighting, conveying efficiency, safety, and modern healthcare logistics. The color palette emphasizes crisp whites, deep teals, and professional grays." style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuASLNd0e7DXqT5o4BRjHi8i3Hmy8JGWGUJbsyViPoXlv4LZnAPV25bx8-f9Rce--arNLxLFiDWJgf59DT2hsDiUYDSKEAPRIN0D9JZlwrrrLLJBAuHqXEGpe3rSCgWad81XhIEtO9Ud5ShLAEVuNTsyVw8iV4n4jQa-i4TzV3600_3Qra3wX2v46waHl1_pblkoc47x_2VGhMowcluCUE6w7c2pirThcGyN0NDTLxrsdnoaYFjxfsg7KYuX0T5m-PTnhPTImxjv7ZTp')"></div>
<div class="absolute inset-0 bg-primary/80 bg-pattern"></div>
</div>
<!-- Foreground Content -->
<div class="relative z-10 w-full max-w-lg text-on-primary">
<img :src="logoLight" alt="MTC" class="h-12 w-auto mb-lg" />
<div class="space-y-xl">
<h2 class="font-display-lg text-display-lg text-on-primary leading-tight">
                        Secure.<br/>Traceable.<br/>Mission-Critical.
                    </h2>
<p class="font-body-lg text-body-lg text-on-primary/90 max-w-md">
                        Empowering couriers and clinical dispatchers with real-time tracking, temperature compliance, and intelligent routing.
                    </p>
<div class="grid grid-cols-2 gap-md pt-lg border-t border-on-primary/20">
<div>
<div class="font-headline-lg text-headline-lg mb-base">99.8%</div>
<div class="font-body-sm text-body-sm text-on-primary/80">On-time Delivery Rate</div>
</div>
<div>
<div class="font-headline-lg text-headline-lg mb-base">Zero</div>
<div class="font-body-sm text-body-sm text-on-primary/80">Temperature Excursions</div>
</div>
</div>
</div>
</div>
<!-- Abstract decorative curve -->
<svg class="absolute bottom-0 left-0 w-full h-64 text-primary-container/30 pointer-events-none" preserveaspectratio="none" viewbox="0 0 1440 320">
<path d="M0,288L48,272C96,256,192,224,288,197.3C384,171,480,149,576,165.3C672,181,768,235,864,250.7C960,267,1056,245,1152,213.3C1248,181,1344,139,1392,117.3L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z" fill="currentColor"></path>
</svg>
</div>
<!-- Right Side: Login Form -->
<div class="w-full lg:w-1/2 flex items-center justify-center p-margin-mobile lg:p-margin-desktop bg-surface relative">
<!-- Mobile Header (Visible only on small screens) -->
<div class="absolute top-0 left-0 w-full p-margin-mobile flex items-center gap-xs lg:hidden z-20">
<img :src="logoDark" alt="MTC" class="h-8 w-auto" />
</div>
<!-- Login Card -->
<div class="w-full max-w-md bg-surface-container-lowest rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.05)] border border-outline-variant/30 p-lg sm:p-[40px] relative z-10 glass-panel">
<div class="mb-lg text-center lg:text-left">
<p class="font-body-md text-body-md text-on-surface-variant mb-base">Welcome back</p>
<h2 class="font-headline-lg-mobile lg:font-headline-lg text-headline-lg-mobile lg:text-headline-lg text-on-surface">Sign in to continue</h2>
</div>
<form method="POST" action="/login" class="space-y-md">
<input type="hidden" name="_token" :value="csrfToken" />
<!-- Mobile Number Field -->
<div>
<label class="block font-label-md text-label-md text-on-surface mb-2 uppercase tracking-wider" for="email">Email Address</label>
<div class="relative">
<div class="absolute inset-y-0 left-0 pl-sm flex items-center pointer-events-none text-on-surface-variant">
<i class="ri-mail-line text-[20px]"></i>
</div>
<input v-model="email"
  class="block w-full rounded-lg py-3 pl-10 pr-sm text-on-surface placeholder:text-on-surface-variant/50 focus:ring-2 focus:ring-opacity-20 transition-shadow font-body-md"
  :class="errors.email ? 'border-error focus:border-error focus:ring-error' : 'border-outline-variant focus:border-primary focus:ring-primary'"
  id="email" name="email" placeholder="admin@example.com" type="email" required autofocus/>
</div>
<p v-if="errors.email" class="flex items-center gap-1.5 text-error text-sm mt-2">
  <i class="ri-error-warning-line text-[15px] shrink-0"></i>{{ errors.email }}
</p>
</div>
<!-- Password Field -->
<div>
<div class="flex justify-between items-center mb-xs">
<label class="block font-label-md text-label-md text-on-surface uppercase" for="password">Password</label>
</div>
<div class="relative">
<div class="absolute inset-y-0 left-0 pl-sm flex items-center pointer-events-none text-on-surface-variant">
<i class="ri-lock-password-line text-[20px]"></i>
</div>
<input v-model="password" :type="showPassword ? 'text' : 'password'"
  class="block w-full rounded-lg py-3 pl-10 pr-10 text-on-surface placeholder:text-on-surface-variant/50 focus:ring-2 focus:ring-opacity-20 transition-shadow font-body-md"
  :class="errors.password ? 'border-error focus:border-error focus:ring-error' : 'border-outline-variant focus:border-primary focus:ring-primary'"
  id="password" name="password" placeholder="••••••••" required />
<button @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-sm flex items-center text-on-surface-variant hover:text-primary transition-colors" type="button">
<i :class="showPassword ? 'ri-eye-off-line' : 'ri-eye-line'" class="text-[20px]"></i>
</button>
</div>
<p v-if="errors.password" class="flex items-center gap-1.5 text-error text-sm mt-2">
  <i class="ri-error-warning-line text-[15px] shrink-0"></i>{{ errors.password }}
</p>
</div>
<!-- Remember & Forgot -->
<div class="flex items-center justify-between pt-xs">
<div class="flex items-center">
<input v-model="remember" class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary" id="remember-me" name="remember" type="checkbox"/>
<label class="ml-2 block font-body-sm text-body-sm text-on-surface-variant" for="remember-me">
                                Remember me
                            </label>
</div>
<div class="text-sm">
<a class="font-label-md text-label-md text-primary hover:text-primary-container transition-colors" href="#">Forgot password?</a>
</div>
</div>
<!-- Submit Button -->
<div class="pt-sm">
<button class="w-full flex justify-center items-center gap-xs py-3 px-4 border border-transparent rounded-lg shadow-sm font-headline-sm text-headline-sm text-on-primary bg-primary hover:bg-primary-container focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed" type="submit">
                            Sign In
                            <i class="ri-arrow-right-line text-[20px]"></i>
</button>
</div>
</form>
<!-- Footer Access -->
<div class="mt-lg text-center">
<p class="font-body-sm text-body-sm text-on-surface-variant">
                        New courier? <a class="font-label-md text-label-md text-primary hover:text-primary-container transition-colors" href="#">Request access</a>
</p>
</div>
</div>
<!-- Background Decoration for Right Side -->
<div class="absolute bottom-0 right-0 w-64 h-64 bg-surface-variant rounded-tl-full opacity-50 pointer-events-none -z-10 blur-3xl"></div>
<div class="absolute top-0 right-1/4 w-32 h-32 bg-primary-container rounded-full opacity-10 pointer-events-none -z-10 blur-2xl"></div>
</div>
</div>

  </div>
</template>

<script>
export default {
    layout: null,
};
</script>

<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// Native browser POST (not an Inertia visit): a successful login redirects to the
// Velzon Blade dashboard (/dashboard), which is NOT an Inertia response — an Inertia
// XHR can't render it and would leave a blank screen. A real form submit lets the
// browser follow the redirect as a normal full-page navigation.
// Same brand logos the sidebar uses (public-path strings so Vite doesn't bundle them).
// Light logo on the dark teal panel; dark logo on the white mobile header.
const logoLight = '/assets/images/logo-light.png';
const logoDark = '/assets/images/logo-dark.png';

// Validation errors are flashed to the session on a failed login and shared back by
// HandleInertiaRequests when /login re-renders; old input repopulates the email.
const page = usePage();
const errors = computed(() => page.props.errors ?? {});

const showPassword = ref(false);
const email = ref(page.props.old?.email ?? '');
const password = ref('');
const remember = ref(false);

// CSRF token from the <meta> tag in app.blade.php (root Inertia view).
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
</script>

<style scoped>

        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .bg-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.2) 1px, transparent 1px);
            background-size: 24px 24px;
        }
      
</style>
