import { getAuthToken } from '@/lib/api-client';

describe('api-client getAuthToken fallback', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('returns token from dailycup-auth.state.token when present', () => {
    const auth = { state: { user: { id: 1 }, token: 'token-from-dailycup-auth', isAuthenticated: true } };
    localStorage.setItem('dailycup-auth', JSON.stringify(auth));
    expect(getAuthToken()).toBe('token-from-dailycup-auth');
  });

  it('falls back to legacy localStorage.token when dailycup-auth is missing', () => {
    localStorage.setItem('token', 'legacy-token');
    expect(getAuthToken()).toBe('legacy-token');
  });

  it('returns null when no token is present', () => {
    expect(getAuthToken()).toBeNull();
  });
});