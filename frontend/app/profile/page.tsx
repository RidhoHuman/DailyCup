"use client";

import { useState } from "react";
import Image from "next/image";
import Header from "../../components/Header";

export default function ProfilePage() {
  const [activeTab, setActiveTab] = useState("profile");
  const [showDemoBanner, setShowDemoBanner] = useState(true);

  // Mock user data
  const [user, setUser] = useState({
    name: "John Doe",
    email: "john.doe@example.com",
    phone: "+62 812-3456-7890",
    address: "Jl. Sudirman No. 123, Jakarta",
    loyaltyPoints: 250,
    joinDate: "January 2024",
    profilePicture: null as string | null
  });

  const [isEditing, setIsEditing] = useState(false);
  const [formData, setFormData] = useState(user);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);

  const [errors, setErrors] = useState<{name?: string; email?: string; phone?: string; general?: string}>({});
  const [isSaving, setIsSaving] = useState(false);

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
      // TODO: Replace with actual API call
      console.log("Saving profile:", formData);
      
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      // Update user data
      setUser({...formData, profilePicture: previewUrl});
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

  return (
    <div className="min-h-screen bg-[#f6efe9]">
      <Header />

      {/* Demo Banner */}
      {showDemoBanner && (
        <div className="bg-amber-100 border-l-4 border-amber-500 p-4 mx-4 mt-4 rounded-r-lg">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <i className="bi bi-info-circle text-amber-600 text-xl"></i>
            </div>
            <div className="ml-3">
              <p className="text-sm text-amber-800 font-medium">
                <strong>Mode Demo</strong> - Data profil ini hanya untuk demonstrasi UI/UX.
              </p>
              <p className="text-sm text-amber-700 mt-1">
                Perubahan yang Anda buat tidak akan tersimpan ke database. Sistem authentication akan diimplementasikan pada fase berikutnya.
              </p>
            </div>
            <div className="ml-auto">
              <button 
                onClick={() => setShowDemoBanner(false)}
                className="text-amber-600 hover:text-amber-800 text-sm font-medium"
              >
                <i className="bi bi-x text-lg"></i>
              </button>
            </div>
          </div>
        </div>
      )}

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
                      <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                        <i className="bi bi-star-fill mr-1"></i>
                        {user.loyaltyPoints} Loyalty Points
                      </span>
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
                  <div className="space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Current Password
                      </label>
                      <input
                        type="password"
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        New Password
                      </label>
                      <input
                        type="password"
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Confirm New Password
                      </label>
                      <input
                        type="password"
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                      />
                    </div>
                    <button className="px-6 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8a5a3d]">
                      Update Password
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
    </div>
  );
}