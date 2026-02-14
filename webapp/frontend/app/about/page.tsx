"use client";

import { useState } from "react";
import Image from "next/image";
import Header from "../../components/Header";

export default function AboutPage() {
  const [imgErrored, setImgErrored] = useState(false);

  return (
    <div className="min-h-screen bg-[#f5f0ec]">
      <Header />

      <div className="max-w-6xl mx-auto px-4 py-16">
        <div className="text-center mb-16">
          <h1 className="text-5xl font-bold text-[#a97456] mb-6 font-['Quantico']">
            About DailyCup
          </h1>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            Where every pour meets precision and every sip tells a story of
            excellence. Discover our journey in crafting the perfect coffee
            experience.
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-16">
          <div>
            <h2 className="text-3xl font-bold text-[#a97456] mb-6">
              Our Story
            </h2>
            <p className="text-gray-700 mb-4">
              Founded in 2022, DailyCup began as a passion project to bring
              exceptional coffee experiences to coffee lovers everywhere. What
              started as a small café has grown into a beloved destination for
              premium coffee and beverages.
            </p>
            <p className="text-gray-700 mb-4">
              We believe that great coffee is about more than just the beans –
              it&apos;s about the connection, the moment, and the experience. Every
              cup we serve is crafted with care, using only the finest
              ingredients and traditional brewing methods.
            </p>
            <p className="text-gray-700">
              Join us in celebrating the art of coffee, one perfect cup at a
              time.
            </p>
          </div>

          <div className="relative h-96 rounded-lg overflow-hidden shadow-lg">
            {/* Image will try to load user-provided file; on client error we show a visual fallback. */}
            <Image
              /* using an existing image so there is no 404 in dev; replace with your jfif later */
              src="/assets/image/orangngopi1.png"
              alt="Our Coffee — Your Mornings"
              fill
              sizes="(max-width: 640px) 100vw, (max-width: 1024px) 80vw, 50vw"
              className="object-cover"
              onError={() => setImgErrored(true)}
              priority={false}
            />

            {/* client-only fallback shown when image fails loading (no SSR mismatch) */}
            {imgErrored && (
              <div className="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-[#a97456] to-[#8a5a3d]">
                <i className="bi bi-cup text-8xl text-white"></i>
              </div>
            )}
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
          <div className="text-center">
            <div className="w-16 h-16 bg-[#a97456] rounded-full flex items-center justify-center mx-auto mb-4">
              <i className="bi bi-star text-white text-2xl"></i>
            </div>
            <h3 className="text-xl font-semibold text-[#a97456] mb-2">
              Premium Quality
            </h3>
            <p className="text-gray-600">
              We source only the finest beans and ingredients for an unmatched
              coffee experience.
            </p>
          </div>

          <div className="text-center">
            <div className="w-16 h-16 bg-[#a97456] rounded-full flex items-center justify-center mx-auto mb-4">
              <i className="bi bi-heart text-white text-2xl"></i>
            </div>
            <h3 className="text-xl font-semibold text-[#a97456] mb-2">
              Made with Love
            </h3>
            <p className="text-gray-600">
              Every drink is prepared with passion and attention to detail by
              our skilled baristas.
            </p>
          </div>

          <div className="text-center">
            <div className="w-16 h-16 bg-[#a97456] rounded-full flex items-center justify-center mx-auto mb-4">
              <i className="bi bi-people text-white text-2xl"></i>
            </div>
            <h3 className="text-xl font-semibold text-[#a97456] mb-2">
              Community First
            </h3>
            <p className="text-gray-600">
              We&apos;re more than a coffee shop – we&apos;re a gathering place for coffee
              enthusiasts.
            </p>
          </div>
        </div>

        <div className="text-center">
          <h2 className="text-3xl font-bold text-[#a97456] mb-6">
            Visit Us Today
          </h2>
          <p className="text-gray-600 mb-8">
            Experience the DailyCup difference. Come taste the perfection for
            yourself.
          </p>
          {/* Embedded Google Maps — responsive container */}
          <div className="w-full h-[400px] mt-8 rounded-xl overflow-hidden shadow-lg border-4 border-amber-900/10 mx-auto">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d987.773453968395!2d112.6170758!3d-7.9892479000000005!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7883c33fe70923%3A0xf7beed96b11b5670!2sKangen%20Kopi!5e0!3m2!1sen!2sid!4v1770987524235!5m2!1sen!2sid"
              width="100%"
              height="100%"
              style={{ border: 0 }}
              allowFullScreen
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
              title="DailyCup Location"
            ></iframe>
          </div>
        </div>
      </div>
    </div>
  );
}
