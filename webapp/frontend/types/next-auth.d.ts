import { DefaultSession, DefaultUser } from "next-auth";
import { JWT, DefaultJWT } from "next-auth/jwt";

declare module "next-auth" {
  interface Session {
    user: {
      id: string;
      role: string;
      loyaltyPoints: number;
    } & DefaultSession["user"];
    accessToken?: string;
  }

  interface User extends DefaultUser {
    role?: string;
    loyaltyPoints?: number;
    token?: string;
  }
}

declare module "next-auth/jwt" {
  interface JWT extends DefaultJWT {
    id: string;
    role: string;
    loyaltyPoints: number;
    accessToken?: string;
  }
}
