import { onMounted, ref } from 'vue';

const STORAGE_KEY = 'cyclesip-theme';
const theme = ref('light');

export function useTheme() {
  const applyTheme = value => {
    theme.value = value;
    document.documentElement.classList.toggle('dark', value === 'dark');
    localStorage.setItem(STORAGE_KEY, value);
  };

  const toggleTheme = () => {
    applyTheme(theme.value === 'dark' ? 'light' : 'dark');
  };

  onMounted(() => {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === 'dark' || stored === 'light') {
      applyTheme(stored);
      return;
    }

    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    applyTheme(prefersDark ? 'dark' : 'light');
  });

  return {
    theme,
    toggleTheme,
  };
}
