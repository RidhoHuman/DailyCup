'use client';

import { useState } from 'react';
import { Share2, Facebook, Twitter, MessageCircle, Link2, Check } from 'lucide-react';

interface SocialShareProps {
  url: string;
  title: string;
  description?: string;
  image?: string;
  className?: string;
  variant?: 'icon' | 'full' | 'dropdown';
}

export function SocialShare({ 
  url, 
  title, 
  description = '', 
  image,
  className = '',
  variant = 'dropdown'
}: SocialShareProps) {
  const [showMenu, setShowMenu] = useState(false);
  const [copied, setCopied] = useState(false);

  // Ensure absolute URL
  const shareUrl = url.startsWith('http') ? url : `${typeof window !== 'undefined' ? window.location.origin : ''}${url}`;
  const encodedUrl = encodeURIComponent(shareUrl);
  const encodedTitle = encodeURIComponent(title);
  const encodedDescription = encodeURIComponent(description);

  const shareLinks = {
    facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
    twitter: `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`,
    whatsapp: `https://wa.me/?text=${encodedTitle}%20${encodedUrl}`,
    telegram: `https://t.me/share/url?url=${encodedUrl}&text=${encodedTitle}`,
  };

  const handleNativeShare = async () => {
    if (typeof navigator !== 'undefined' && navigator.share) {
      try {
        await navigator.share({
          title,
          text: description,
          url: shareUrl,
        });
      } catch (error) {
        // User cancelled or error occurred
        console.log('Share cancelled or failed:', error);
      }
    } else {
      // Fallback to showing menu
      setShowMenu(!showMenu);
    }
  };

  const handleCopyLink = async () => {
    try {
      await navigator.clipboard.writeText(shareUrl);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (error) {
      console.error('Failed to copy:', error);
    }
  };

  const handleSocialClick = (platform: keyof typeof shareLinks) => {
    window.open(shareLinks[platform], '_blank', 'width=600,height=400');
    setShowMenu(false);
  };

  if (variant === 'icon') {
    return (
      <button
        onClick={handleNativeShare}
        className={`p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors ${className}`}
        title="Share"
      >
        <Share2 className="w-5 h-5" />
      </button>
    );
  }

  if (variant === 'full') {
    return (
      <button
        onClick={handleNativeShare}
        className={`flex items-center gap-2 px-4 py-2 rounded-full border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors ${className}`}
      >
        <Share2 className="w-5 h-5" />
        <span>Share</span>
      </button>
    );
  }

  // Dropdown variant (default)
  return (
    <div className={`relative ${className}`}>
      <button
        onClick={() => setShowMenu(!showMenu)}
        className="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        title="Share"
      >
        <Share2 className="w-5 h-5" />
      </button>

      {showMenu && (
        <>
          {/* Backdrop */}
          <div 
            className="fixed inset-0 z-40" 
            onClick={() => setShowMenu(false)}
          />
          
          {/* Dropdown Menu */}
          <div className="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden z-50">
            <div className="p-2 space-y-1">
              {/* Native Share (if supported) */}
              {typeof navigator !== 'undefined' && 'share' in navigator && (
                <button
                  onClick={handleNativeShare}
                  className="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-left"
                >
                  <Share2 className="w-5 h-5 text-gray-600 dark:text-gray-400" />
                  <span className="text-sm font-medium">Share via...</span>
                </button>
              )}

              {/* Facebook */}
              <button
                onClick={() => handleSocialClick('facebook')}
                className="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-left"
              >
                <Facebook className="w-5 h-5 text-[#1877F2]" />
                <span className="text-sm font-medium">Facebook</span>
              </button>

              {/* Twitter */}
              <button
                onClick={() => handleSocialClick('twitter')}
                className="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-left"
              >
                <Twitter className="w-5 h-5 text-[#1DA1F2]" />
                <span className="text-sm font-medium">Twitter</span>
              </button>

              {/* WhatsApp */}
              <button
                onClick={() => handleSocialClick('whatsapp')}
                className="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-left"
              >
                <MessageCircle className="w-5 h-5 text-[#25D366]" />
                <span className="text-sm font-medium">WhatsApp</span>
              </button>

              {/* Telegram */}
              <button
                onClick={() => handleSocialClick('telegram')}
                className="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-left"
              >
                <svg className="w-5 h-5 text-[#0088cc]" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                </svg>
                <span className="text-sm font-medium">Telegram</span>
              </button>

              <div className="border-t border-gray-200 dark:border-gray-700 my-1" />

              {/* Copy Link */}
              <button
                onClick={handleCopyLink}
                className="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-left"
              >
                {copied ? (
                  <>
                    <Check className="w-5 h-5 text-green-500" />
                    <span className="text-sm font-medium text-green-500">Link Copied!</span>
                  </>
                ) : (
                  <>
                    <Link2 className="w-5 h-5 text-gray-600 dark:text-gray-400" />
                    <span className="text-sm font-medium">Copy Link</span>
                  </>
                )}
              </button>
            </div>
          </div>
        </>
      )}
    </div>
  );
}
