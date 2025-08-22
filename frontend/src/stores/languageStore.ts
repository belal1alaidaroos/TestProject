import { create } from 'zustand';
import { persist } from 'zustand/middleware';

interface LanguageState {
  language: 'en' | 'ar';
}

interface LanguageActions {
  setLanguage: (language: 'en' | 'ar') => void;
  toggleLanguage: () => void;
}

type LanguageStore = LanguageState & LanguageActions;

export const useLanguageStore = create<LanguageStore>()(
  persist(
    (set, get) => ({
      // State
      language: 'en',

      // Actions
      setLanguage: (language: 'en' | 'ar') => {
        set({ language });
      },

      toggleLanguage: () => {
        const { language } = get();
        set({ language: language === 'en' ? 'ar' : 'en' });
      },
    }),
    {
      name: 'language-storage',
    }
  )
);