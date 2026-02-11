"use client";

import { useState, useEffect } from "react";
import Image from "next/image";
import { api } from "@/lib/api-client";

export default function AdminProfilePage() {
  const [editing, setEditing] = useState(false);
  const [saved, setSaved] = useState(false);
  const [saving, setSaving] = useState(false);
  const [profileData, setProfileData] = useState({
    name: "Admin User",
    email: "admin@dailycup.com",
    phone: "+62 812-3456-7890",
    role: "Super Admin",
    bio: "Managing DailyCup Coffee operations",
    avatar: null as File | null,
  });

  const [passwordData, setPasswordData] = useState({
    currentPassword: "",
    newPassword: "",
    confirmPassword: "",
  });

  useEffect(() => {
    fetchProfile();
  }, []);

  const fetchProfile = async () => {
    try {
      const response = await api.get<{ success: boolean; data: typeof profileData }>('/admin/profile.php');
      if (response.success && response.data) {
        setProfileData(prev => ({
          ...prev,
          name: response.data.name || prev.name,
          email: response.data.email || prev.email,
          phone: response.data.phone || prev.phone,
          role: response.data.role || prev.role,
          bio: response.data.bio || prev.bio,
        }));
      }
    } catch (error) {
      console.error('Error fetching profile:', error);
    }
  };

  const handleSaveProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    
    try {
      const response = await api.put<{ success: boolean; message: string }>('/admin/profile.php', {
        name: profileData.name,
        email: profileData.email,
        phone: profileData.phone,
        bio: profileData.bio,
      });
      
      if (response.success) {
        setEditing(false);
        setSaved(true);
        setTimeout(() => setSaved(false), 3000);
        // Refresh profile data from server
        await fetchProfile();
      }
    } catch (error) {
      console.error('Error saving profile:', error);
      alert('Failed to save profile');
    } finally {
      setSaving(false);
    }
  };

  const handleChangePassword = async (e: React.FormEvent) => {
    e.preventDefault();
    if (passwordData.newPassword !== passwordData.confirmPassword) {
      alert("New passwords don't match!");
      return;
    }
    
    try {
      const response = await api.post<{ success: boolean; message: string }>('/admin/profile.php?action=change-password', {
        currentPassword: passwordData.currentPassword,
        newPassword: passwordData.newPassword,
      });
      
      if (response.success) {
        setPasswordData({ currentPassword: "", newPassword: "", confirmPassword: "" });
        alert("Password changed successfully!");
      }
    } catch (error) {
      console.error('Error changing password:', error);
      alert('Failed to change password');
    }
  };

  return (
    <div className="max-w-4xl">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800">My Profile</h1>
        <p className="text-gray-500">Manage your account settings and preferences</p>
      </div>

      {saved && (
        <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
          ✓ Profile updated successfully!
        </div>
      )}

      {/* Profile Information */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div className="flex justify-between items-center mb-6">
          <h2 className="text-lg font-bold text-gray-800">Profile Information</h2>
          <button
            onClick={() => setEditing(!editing)}
            className="text-sm text-[#a97456] hover:text-[#8f6249] font-medium"
          >
            {editing ? "Cancel" : "Edit Profile"}
          </button>
        </div>

        <form onSubmit={handleSaveProfile}>
          {/* Avatar */}
          <div className="flex items-center gap-6 mb-6 pb-6 border-b border-gray-200">
            <div className="w-24 h-24 rounded-full bg-[#a97456] flex items-center justify-center text-white text-3xl font-bold shadow-lg">
              {profileData.avatar ? (
                <Image
                  src={URL.createObjectURL(profileData.avatar)}
                  alt="Profile"
                  width={96}
                  height={96}
                  className="rounded-full object-cover"
                />
              ) : (
                "AU"
              )}
            </div>
            <div>
              <h3 className="font-bold text-gray-800 mb-1">{profileData.name}</h3>
              <p className="text-sm text-gray-500 mb-3">{profileData.role}</p>
              {editing && (
                <label className="cursor-pointer">
                  <input
                    type="file"
                    accept="image/*"
                    onChange={(e) =>
                      setProfileData({ ...profileData, avatar: e.target.files?.[0] || null })
                    }
                    className="hidden"
                  />
                  <span className="text-sm text-[#a97456] hover:text-[#8f6249] font-medium">
                    Change Photo
                  </span>
                </label>
              )}
            </div>
          </div>

          {/* Form Fields */}
          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Full Name
                </label>
                <input
                  type="text"
                  value={profileData.name}
                  onChange={(e) => setProfileData({ ...profileData, name: e.target.value })}
                  disabled={!editing}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent disabled:bg-gray-50 disabled:text-gray-500"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Email Address
                </label>
                <input
                  type="email"
                  value={profileData.email}
                  onChange={(e) => setProfileData({ ...profileData, email: e.target.value })}
                  disabled={!editing}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent disabled:bg-gray-50 disabled:text-gray-500"
                />
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Phone Number
                </label>
                <input
                  type="tel"
                  value={profileData.phone}
                  onChange={(e) => setProfileData({ ...profileData, phone: e.target.value })}
                  disabled={!editing}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent disabled:bg-gray-50 disabled:text-gray-500"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Role
                </label>
                <input
                  type="text"
                  value={profileData.role}
                  disabled
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Bio</label>
              <textarea
                value={profileData.bio}
                onChange={(e) => setProfileData({ ...profileData, bio: e.target.value })}
                disabled={!editing}
                rows={3}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent disabled:bg-gray-50 disabled:text-gray-500"
              />
            </div>
          </div>

          {editing && (
            <div className="mt-6 pt-6 border-t border-gray-200">
              <button
                type="submit"
                className="px-6 py-2 bg-[#a97456] text-white rounded-lg font-medium hover:bg-[#8f6249] transition-colors"
              >
                Save Changes
              </button>
            </div>
          )}
        </form>
      </div>

      {/* Change Password */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 className="text-lg font-bold text-gray-800 mb-6">Change Password</h2>
        <form onSubmit={handleChangePassword} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Current Password
            </label>
            <input
              type="password"
              value={passwordData.currentPassword}
              onChange={(e) =>
                setPasswordData({ ...passwordData, currentPassword: e.target.value })
              }
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
              required
            />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                New Password
              </label>
              <input
                type="password"
                value={passwordData.newPassword}
                onChange={(e) =>
                  setPasswordData({ ...passwordData, newPassword: e.target.value })
                }
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Confirm New Password
              </label>
              <input
                type="password"
                value={passwordData.confirmPassword}
                onChange={(e) =>
                  setPasswordData({ ...passwordData, confirmPassword: e.target.value })
                }
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                required
              />
            </div>
          </div>
          <button
            type="submit"
            className="px-6 py-2 bg-[#a97456] text-white rounded-lg font-medium hover:bg-[#8f6249] transition-colors"
          >
            Update Password
          </button>
        </form>
      </div>
    </div>
  );
}
