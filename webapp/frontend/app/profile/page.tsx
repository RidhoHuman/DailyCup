"use client";

import { useState, useEffect } from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import Header from "../../components/Header";
import { useAuthStore, useAuthHydration } from "@/lib/stores/auth-store";import type { User } from '@/lib/stores/auth-store';
import { getErrorMessage } from '@/lib/utils';import { api as apiClient } from "@/lib/api-client";

export default function ProfilePage() {
  const router = useRouter();
  const { user: authUser, token, isAuthenticated, updateUser } = useAuthStore();
  const isHydrated = useAuthHydration();
  const [activeTab, setActiveTab] = useState("profile");
  const [showDemoBanner, setShowDemoBanner] = useState(false);
  const [isLoadingProfile, setIsLoadingProfile] = useState(true);

  // User data from API
  const [user, setUser] = useState({
    name: authUser?.name || "",
    email: authUser?.email || "",
    phone: authUser?.phone || "",
    address: authUser?.address || "",
    loyaltyPoints: authUser?.loyaltyPoints || 0,
    joinDate: authUser?.joinDate || "",
    profilePicture: authUser?.profilePicture || null
  });

  // Fetch user profile from API on mount - Wait for hydration first
  useEffect(() => {
    // Don't check auth until store is hydrated
    if (!isHydrated) return;
    
    if (!isAuthenticated || !token) {
      router.push('/login');
      return;
    }

    const fetchProfile = async () => {
      try {
        setIsLoadingProfile(true);
        const response = await apiClient.get<{success: boolean; user: User}>('/me.php', { requiresAuth: true });
        
        if (response.success && response.user) {
          const userData = {
            name: response.user.name || "",
            email: response.user.email || "",
            phone: response.user.phone || "",
            address: response.user.address || "",
            loyaltyPoints: response.user.loyaltyPoints || 0,
            joinDate: response.user.joinDate || "",
            profilePicture: response.user.profilePicture || null
          };
          setUser(userData);
          setFormData(userData);
          
          // Update auth store with fresh data
          updateUser({
            name: response.user.name,
            email: response.user.email,
            phone: response.user.phone,
            address: response.user.address,
            loyaltyPoints: response.user.loyaltyPoints,
            profilePicture: response.user.profilePicture
          });
        }
      } catch (error: unknown) {
        console.error("Failed to fetch profile:", getErrorMessage(error));
      } finally {
        setIsLoadingProfile(false);
      }
    };

    fetchProfile();
  }, [isAuthenticated, token, router, updateUser]);

  const [isEditing, setIsEditing] = useState(false);
  const [formData, setFormData] = useState(user);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);

  const [errors, setErrors] = useState<{name?: string; email?: string; phone?: string; general?: string}>({});
  const [isSaving, setIsSaving] = useState(false);

  // Password change state
  const [passwordData, setPasswordData] = useState({
    currentPassword: "",
    newPassword: "",
    confirmPassword: ""
  });
  const [passwordErrors, setPasswordErrors] = useState<{currentPassword?: string; newPassword?: string; confirmPassword?: string; general?: string}>({});
  const [isChangingPassword, setIsChangingPassword] = useState(false);
  const [passwordSuccess, setPasswordSuccess] = useState(false);

  const validateForm = () => {
    const newErrors: {name?: string; email?: string; phone?: string} = {};
    
    if (!formData.name.trim()) {
      newErrors.name = "Name is required";
    }
    
    if (!formData.email.trim()) {
      newErrors.email = "Email is required";
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = "Please enter a valid email address";
    }
    
    if (!formData.phone.trim()) {
      newErrors.phone = "Phone number is required";
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSave = async () => {
    setErrors({});
    
    if (!validateForm()) return;
    
    setIsSaving(true);
    try {
      // TODO: Implement profile update API endpoint
      // For now, just update local state
      console.log("Saving profile:", formData);
      
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      // Update user data
      const updatedUser = {...formData, profilePicture: previewUrl};
      setUser(updatedUser);
      
      // Update auth store
      updateUser({
        name: updatedUser.name,
        email: updatedUser.email,
        phone: updatedUser.phone,
        address: updatedUser.address,
        profilePicture: updatedUser.profilePicture || undefined
      });
      
      setIsEditing(false);
      setSelectedFile(null);
      setPreviewUrl(null);
      
      alert("Profile updated successfully!");
    } catch (error) {
      console.error("Save profile error:", error);
      setErrors({ general: "Failed to save profile. Please try again." });
    } finally {
      setIsSaving(false);
    }
  };

  const handleCancel = () => {
    setFormData(user);
    setIsEditing(false);
    setSelectedFile(null);
    setPreviewUrl(null);
  };

  const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (file) {
      setSelectedFile(file);
      const reader = new FileReader();
      reader.onload = (e) => {
        setPreviewUrl(e.target?.result as string);
      };
      reader.readAsDataURL(file);
    }
  };

  const removeProfilePicture = () => {
    setPreviewUrl(null);
    setSelectedFile(null);
    setFormData({...formData, profilePicture: null});
  };

  const validatePasswordForm = () => {
    const newErrors: {currentPassword?: string; newPassword?: string; confirmPassword?: string} = {};
    
    if (!passwordData.currentPassword.trim()) {
      newErrors.currentPassword = "Current password is required";
    }
    
    if (!passwordData.newPassword.trim()) {
      newErrors.newPassword = "New password is required";
    } else if (passwordData.newPassword.length < 6) {
      newErrors.newPassword = "Password must be at least 6 characters";
    }
    
    if (passwordData.newPassword !== passwordData.confirmPassword) {
      newErrors.confirmPassword = "Passwords do not match";
    }
    
    setPasswordErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleChangePassword = async () => {
    setPasswordErrors({});
    setPasswordSuccess(false);
    
    if (!validatePasswordForm()) return;
    
    setIsChangingPassword(true);
    try {
      const response = await apiClient.put<{success: boolean; message?: string}>('/change_password.php', {
        currentPassword: passwordData.currentPassword,
        newPassword: passwordData.newPassword
      });
      
      if (response.success) {
        setPasswordSuccess(true);
        setPasswordData({ currentPassword: "", newPassword: "", confirmPassword: "" });
        alert("Password changed successfully!");
        setTimeout(() => setPasswordSuccess(false), 3000);
      }
    } catch (error: unknown) {
      console.error("Change password error:", getErrorMessage(error));
      const errorMsg = (error as any)?.response?.data?.message || getErrorMessage(error) || "Failed to change password";
      setPasswordErrors({ general: errorMsg });
    } finally {
      setIsChangingPassword(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#f6efe9]">
      <Header />

      {isLoadingProfile ? (
        <div className="max-w-6xl mx-auto px-4 py-8">
          <div className="bg-white rounded-2xl shadow-lg p-12 text-center">
            <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456]"></div>
            <p className="mt-4 text-gray-600">Loading profile...</p>
          </div>
        </div>
      ) : (
        <div className="max-w-6xl mx-auto px-4 py-8">
        <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
          {/* Header */}
          <div className="bg-[#a97456] text-white p-6">
            <h1 className="text-3xl font-bold">My Profile</h1>
            <p className="text-amber-100 mt-2">Manage your account information</p>
          </div>

          {/* Tabs */}
          <div className="border-b">
            <nav className="flex">
              <button
                onClick={() => setActiveTab("profile")}
                className={`px-6 py-4 font-medium ${
                  activeTab === "profile"
                    ? "border-b-2 border-[#a97456] text-[#a97456]"
                    : "text-gray-600 hover:text-[#a97456]"
                }`}
              >
                Profile Information
              </button>
              <button
                onClick={() => setActiveTab("orders")}
                className={`px-6 py-4 font-medium ${
                  activeTab === "orders"
                    ? "border-b-2 border-[#a97456] text-[#a97456]"
                    : "text-gray-600 hover:text-[#a97456]"
                }`}
              >
                Order History
              </button>
              <button
                onClick={() => setActiveTab("settings")}
                className={`px-6 py-4 font-medium ${
                  activeTab === "settings"
                    ? "border-b-2 border-[#a97456] text-[#a97456]"
                    : "text-gray-600 hover:text-[#a97456]"
                }`}
              >
                Account Settings
              </button>
            </nav>
          </div>

          {/* Content */}
          <div className="p-6">
            {activeTab === "profile" && (
              <div className="space-y-6">
                {/* Profile Picture */}
                <div className="flex items-center space-x-6">
                  <div className="relative">
                    <div className="w-24 h-24 bg-[#a97456] rounded-full flex items-center justify-center overflow-hidden">
                      {previewUrl || user.profilePicture ? (
                        <Image
                          src={previewUrl || user.profilePicture || ""}
                          alt="Profile"
                          width={96}
                          height={96}
                          className="w-full h-full object-cover rounded-full"
                        />
                      ) : (
                        <i className="bi bi-person text-4xl text-white"></i>
                      )}
                    </div>
                    {isEditing && (
                      <div className="absolute -bottom-2 -right-2 flex space-x-1">
                        <label className="w-8 h-8 bg-[#a97456] rounded-full flex items-center justify-center cursor-pointer hover:bg-[#8a5a3d] transition-colors">
                          <i className="bi bi-camera text-white text-sm"></i>
                          <input
                            type="file"
                            accept="image/*"
                            onChange={handleFileSelect}
                            className="hidden"
                          />
                        </label>
                        {(previewUrl || user.profilePicture) && (
                          <button
                            onClick={removeProfilePicture}
                            className="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center hover:bg-red-600 transition-colors"
                          >
                            <i className="bi bi-x text-white text-sm"></i>
                          </button>
                        )}
                      </div>
                    )}
                  </div>
                  <div>
                    <h3 className="text-xl font-semibold text-gray-800">{user.name}</h3>
                    <p className="text-gray-600">Member since {user.joinDate}</p>
                    <div className="mt-2">
                      <Link 
                        href="/profile/loyalty"
                        className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800 hover:bg-amber-200 transition-colors"
                      >
                        <i className="bi bi-star-fill mr-1"></i>
                        {user.loyaltyPoints} Loyalty Points
                      </Link>
                    </div>
                    {selectedFile && (
                      <p className="text-sm text-green-600 mt-2">
                        <i className="bi bi-check-circle mr-1"></i>
                        {selectedFile.name} selected
                      </p>
                    )}
                  </div>
                </div>

                {/* Profile Form */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  {errors.general && (
                    <div className="col-span-full p-3 bg-red-50 border border-red-200 rounded-lg">
                      <p className="text-red-600 text-sm">{errors.general}</p>
                    </div>
                  )}
                  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Full Name
                    </label>
                    {isEditing ? (
                      <>
                        <input
                          type="text"
                          value={formData.name}
                          onChange={(e) => setFormData({...formData, name: e.target.value})}
                          className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent ${
                            errors.name ? 'border-red-300' : 'border-gray-300'
                          }`}
                        />
                        {errors.name && (
                          <p className="text-red-600 text-sm mt-1">{errors.name}</p>
                        )}
                      </>
                    ) : (
                      <p className="text-gray-900 bg-gray-50 px-3 py-2 rounded-lg">{user.name}</p>
                    )}
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Email Address
                    </label>
                    {isEditing ? (
                      <>
                        <input
                          type="email"
                          value={formData.email}
                          onChange={(e) => setFormData({...formData, email: e.target.value})}
                          className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent ${
                            errors.email ? 'border-red-300' : 'border-gray-300'
                          }`}
                        />
                        {errors.email && (
                          <p className="text-red-600 text-sm mt-1">{errors.email}</p>
                        )}
                      </>
                    ) : (
                      <p className="text-gray-900 bg-gray-50 px-3 py-2 rounded-lg">{user.email}</p>
                    )}
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Phone Number
                    </label>
                    {isEditing ? (
                      <>
                        <input
                          type="tel"
                          value={formData.phone}
                          onChange={(e) => setFormData({...formData, phone: e.target.value})}
                          className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent ${
                            errors.phone ? 'border-red-300' : 'border-gray-300'
                          }`}
                        />
                        {errors.phone && (
                          <p className="text-red-600 text-sm mt-1">{errors.phone}</p>
                        )}
                      </>
                    ) : (
                      <p className="text-gray-900 bg-gray-50 px-3 py-2 rounded-lg">{user.phone}</p>
                    )}
                  </div>

                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Address
                    </label>
                    {isEditing ? (
                      <textarea
                        value={formData.address}
                        onChange={(e) => setFormData({...formData, address: e.target.value})}
                        rows={3}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                      />
                    ) : (
                      <p className="text-gray-900 bg-gray-50 px-3 py-2 rounded-lg">{user.address}</p>
                    )}
                  </div>
                </div>

                {/* Action Buttons */}
                <div className="flex justify-end space-x-4">
                  {isEditing ? (
                    <>
                      <button
                        onClick={handleCancel}
                        className="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                      >
                        Cancel
                      </button>
                      <button
                        onClick={handleSave}
                        disabled={isSaving}
                        className="px-6 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8a5a3d] disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        {isSaving ? "Saving..." : "Save Changes"}
                      </button>
                    </>
                  ) : (
                    <button
                      onClick={() => setIsEditing(true)}
                      className="px-6 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8a5a3d]"
                    >
                      Edit Profile
                    </button>
                  )}
                </div>
              </div>
            )}

            {activeTab === "orders" && (
              <div className="space-y-4">
                <div className="flex justify-between items-center">
                  <h3 className="text-xl font-semibold text-gray-800">Order History</h3>
                  <a
                    href="/orders"
                    className="px-4 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8a5a3d] text-sm"
                  >
                    View All Orders
                  </a>
                </div>
                <div className="text-center py-12">
                  <i className="bi bi-receipt text-6xl text-gray-300"></i>
                  <p className="text-gray-500 mt-4">No orders yet</p>
                  <p className="text-sm text-gray-400">Your order history will appear here</p>
                </div>
              </div>
            )}

            {activeTab === "settings" && (
              <div className="space-y-6">
                <h3 className="text-xl font-semibold text-gray-800">Account Settings</h3>

                {/* Change Password */}
                <div className="border rounded-lg p-4">
                  <h4 className="font-medium text-gray-800 mb-4">Change Password</h4>
                  
                  {passwordSuccess && (
                    <div className="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                      ✓ Password changed successfully!
                    </div>
                  )}
                  
                  {passwordErrors.general && (
                    <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                      ✗ {passwordErrors.general}
                    </div>
                  )}
                  
                  <div className="space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Current Password
                      </label>
                      <input
                        type="password"
                        value={passwordData.currentPassword}
                        onChange={(e) => setPasswordData({...passwordData, currentPassword: e.target.value})}
                        className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent ${
                          passwordErrors.currentPassword ? "border-red-500" : "border-gray-300"
                        }`}
                      />
                      {passwordErrors.currentPassword && (
                        <p className="text-red-500 text-sm mt-1">{passwordErrors.currentPassword}</p>
                      )}
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        New Password
                      </label>
                      <input
                        type="password"
                        value={passwordData.newPassword}
                        onChange={(e) => setPasswordData({...passwordData, newPassword: e.target.value})}
                        className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent ${
                          passwordErrors.newPassword ? "border-red-500" : "border-gray-300"
                        }`}
                      />
                      {passwordErrors.newPassword && (
                        <p className="text-red-500 text-sm mt-1">{passwordErrors.newPassword}</p>
                      )}
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Confirm New Password
                      </label>
                      <input
                        type="password"
                        value={passwordData.confirmPassword}
                        onChange={(e) => setPasswordData({...passwordData, confirmPassword: e.target.value})}
                        className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent ${
                          passwordErrors.confirmPassword ? "border-red-500" : "border-gray-300"
                        }`}
                      />
                      {passwordErrors.confirmPassword && (
                        <p className="text-red-500 text-sm mt-1">{passwordErrors.confirmPassword}</p>
                      )}
                    </div>
                    <button
                      onClick={handleChangePassword}
                      disabled={isChangingPassword}
                      className="px-6 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8a5a3d] disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {isChangingPassword ? "Updating..." : "Update Password"}
                    </button>
                  </div>
                </div>

                {/* Account Actions */}
                <div className="border rounded-lg p-4">
                  <h4 className="font-medium text-gray-800 mb-4">Account Actions</h4>
                  <div className="space-y-2">
                    <button className="w-full text-left px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg">
                      Delete Account
                    </button>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
        </div>
      )}
    </div>
  );
}