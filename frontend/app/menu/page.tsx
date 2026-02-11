import { Suspense } from "react";
import MenuClient from "../../components/MenuClient";

export default function MenuPage() {
  return (
    <Suspense fallback={<div className="min-h-screen bg-gray-50">Loading...</div>}>
      <MenuClient />
    </Suspense>
  );
}
