import axios from 'axios';
import './map-cable-enhancements';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
