import './bootstrap';
import { createApp } from 'vue';
import App from './src/App.vue';
import router from './src/router/router';
import ui from '@nuxt/ui/vue-plugin'
import '../css/app.css';

const app = createApp(App)

app.use(router)
app.use(ui)
app.mount('#app');
