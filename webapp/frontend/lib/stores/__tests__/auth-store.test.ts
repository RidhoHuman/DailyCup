import { useAuthStore } from '../auth-store';

describe('auth-store persistence compatibility', () => {
  beforeEach(() => {
    // reset store state
    useAuthStore.setState({ user: null, token: null, isAuthenticated: false, _hasHydrated: false });
    jest.clearAllMocks();
  });

  it('login sets token in store and mirrors to localStorage.token', () => {
    const spySet = jest.spyOn(Storage.prototype, 'setItem');
    const user = { id: '1', name: 'Admin', email: 'admin@example.com', role: 'admin', loyaltyPoints: 0, joinDate: new Date().toISOString() };
    const token = 'ci-test-token';

    useAuthStore.getState().login(user as any, token);

    const state = useAuthStore.getState();
    expect(state.token).toBe(token);
    expect(state.user).toBeDefined();
    // localStorage should be called to mirror legacy `token`
    expect(spySet).toHaveBeenCalledWith('token', token);
    spySet.mockRestore();
  });

  it('logout clears store and removes legacy token key', () => {
    const spyRemove = jest.spyOn(Storage.prototype, 'removeItem');
    const user = { id: '1', name: 'Admin', email: 'admin@example.com', role: 'admin', loyaltyPoints: 0, joinDate: new Date().toISOString() };
    const token = 'ci-test-token';
    useAuthStore.getState().login(user as any, token);

    useAuthStore.getState().logout();

    const state = useAuthStore.getState();
    expect(state.token).toBeNull();
    expect(state.user).toBeNull();
    expect(spyRemove).toHaveBeenCalledWith('token');
    spyRemove.mockRestore();
  });
});