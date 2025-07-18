import { useState, useCallback } from "react";

export interface ProfileType {
  firstName: string;
  lastName: string;
  email: string;
  avatar?: string;
  phone?: string;
  gender?: string;
  country?: string;
  created_at?: string;
  last_login?: string;
  active?: number;
}

export function useProfile() {
  const [profile, setProfile] = useState<ProfileType | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string>("");

  const fetchProfile = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const res = await fetch('http://localhost/vault-new/api/user/profile.php', { credentials: 'include' });
      if (!res.ok) throw new Error('Could not load profile');
      const data = await res.json();
      setProfile({
        firstName: data.first_name || "",
        lastName: data.last_name || "",
        email: data.email || "",
        avatar: data.avatar || "",
        phone: data.phone || "",
        gender: data.gender || "",
        country: data.country || "",
        created_at: data.created_at || "",
        last_login: data.last_login || "",
        active: typeof data.active === 'number' ? data.active : (data.active ? 1 : 0)
      });
    } catch (e: any) {
      setError(e.message || 'Could not load profile');
      setProfile(null);
    } finally {
      setLoading(false);
    }
  }, []);

  const updateProfile = useCallback(async (updated: Partial<ProfileType>) => {
    setLoading(true);
    setError("");
    try {
      const res = await fetch('http://localhost/vault-new/api/user/profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(updated),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Failed to update profile');
      setProfile(prev => prev ? { ...prev, ...updated } : prev);
      return { success: true };
    } catch (e: any) {
      setError(e.message || 'Failed to update profile');
      return { success: false, error: e.message };
    } finally {
      setLoading(false);
    }
  }, []);

  const uploadAvatar = useCallback(async (file: File) => {
    setLoading(true);
    setError("");
    try {
      const formData = new FormData();
      formData.append('avatar', file);
      const res = await fetch('http://localhost/vault-new/api/user/upload-avatar.php', {
        method: 'POST',
        credentials: 'include',
        body: formData,
      });
      const data = await res.json();
      if (data.success && data.avatar) {
        setProfile(prev => prev ? { ...prev, avatar: data.avatar } : prev);
        return { success: true, avatar: data.avatar };
      } else {
        throw new Error(data.error || 'Failed to upload avatar');
      }
    } catch (e: any) {
      setError(e.message || 'Failed to upload avatar');
      return { success: false, error: e.message };
    } finally {
      setLoading(false);
    }
  }, []);

  return {
    profile,
    loading,
    error,
    fetchProfile,
    updateProfile,
    uploadAvatar,
    setProfile,
  };
} 