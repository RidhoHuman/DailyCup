import type { NextAuthConfig } from "next-auth";
import Google from "next-auth/providers/google";
import Facebook from "next-auth/providers/facebook";
import Apple from "next-auth/providers/apple";
import Credentials from "next-auth/providers/credentials";

export const authConfig = {
  providers: [
    Google({
      clientId: process.env.GOOGLE_CLIENT_ID!,
      clientSecret: process.env.GOOGLE_CLIENT_SECRET!,
      authorization: {
        params: {
          prompt: "consent",
          access_type: "offline",
          response_type: "code"
        }
      }
    }),
    Facebook({
      clientId: process.env.FACEBOOK_APP_ID!,
      clientSecret: process.env.FACEBOOK_APP_SECRET!,
    }),
    Apple({
      clientId: process.env.APPLE_CLIENT_ID!,
      clientSecret: process.env.APPLE_CLIENT_SECRET!,
    }),
    Credentials({
      name: "Credentials",
      credentials: {
        email: { label: "Email", type: "email" },
        password: { label: "Password", type: "password" }
      },
      async authorize(credentials) {
        if (!credentials?.email || !credentials?.password) {
          return null;
        }

        try {
          // Call PHP backend API for authentication
          const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/auth.php`, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              action: "login",
              email: credentials.email,
              password: credentials.password,
            }),
          });

          const data = await response.json();

          if (data.success && data.user) {
            return {
              id: data.user.id,
              email: data.user.email,
              name: data.user.name,
              image: data.user.profile_picture || null,
              role: data.user.role || "customer",
              loyaltyPoints: data.user.loyalty_points || 0,
              token: data.token,
            };
          }

          return null;
        } catch (error) {
          console.error("Auth error:", error);
          return null;
        }
      },
    }),
  ],
  pages: {
    signIn: "/login",
    signOut: "/",
    error: "/login",
  },
  callbacks: {
    async jwt({ token, user, account }) {
      if (user) {
        token.id = user.id || '';
        token.role = user.role || 'customer';
        token.loyaltyPoints = user.loyaltyPoints || 0;
        token.accessToken = user.token || account?.access_token;
      }
      return token;
    },
    async session({ session, token }) {
      if (token && session.user) {
        session.user.id = token.id as string;
        session.user.role = token.role as string;
        session.user.loyaltyPoints = token.loyaltyPoints as number;
        session.accessToken = token.accessToken as string;
      }
      return session;
    },
    async signIn({ user, account, profile }) {
      // For OAuth providers, sync with PHP backend
      if (account?.provider !== "credentials") {
        try {
          const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/auth.php`, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              action: "oauth_login",
              provider: account?.provider,
              email: user.email,
              name: user.name,
              picture: user.image,
              oauth_id: account?.providerAccountId,
            }),
          });

          const data = await response.json();
          
          if (data.success) {
            user.id = data.user.id;
            user.role = data.user.role || "customer";
            user.loyaltyPoints = data.user.loyalty_points || 0;
            user.token = data.token;
            return true;
          }
        } catch (error) {
          console.error("OAuth sync error:", error);
        }
      }
      return true;
    },
  },
  session: {
    strategy: "jwt",
    maxAge: 30 * 24 * 60 * 60, // 30 days
  },
  secret: process.env.NEXTAUTH_SECRET,
} satisfies NextAuthConfig;
