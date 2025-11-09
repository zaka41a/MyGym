import { create } from "zustand";

interface NavState {
  isOpen: boolean;
  toggle: () => void;
  close: () => void;
}

export const useNavStore = create<NavState>((set) => ({
  isOpen: false,
  toggle: () => set((state) => ({ isOpen: !state.isOpen })),
  close: () => set({ isOpen: false })
}));
