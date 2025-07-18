import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import React from "react";

interface ProfileInfoFormProps {
  profile: {
    firstName: string;
    lastName: string;
    email: string;
    phone?: string;
    gender?: string;
    country?: string;
  };
  isEditing: boolean;
  onChange: (field: string, value: string) => void;
  validationErrors?: { email?: string; phone?: string };
}

export const ProfileInfoForm: React.FC<ProfileInfoFormProps> = ({
  profile,
  isEditing,
  onChange,
  validationErrors = {},
}) => (
  <Card className="bg-gray-900/50 border-gray-800">
    <CardHeader>
      <CardTitle className="text-white">Personal Information</CardTitle>
      <CardDescription className="text-gray-400">
        Update your personal details
      </CardDescription>
    </CardHeader>
    <CardContent className="space-y-4">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <Label htmlFor="firstName" className="text-white">First Name</Label>
          <Input
            id="firstName"
            value={profile.firstName}
            onChange={e => onChange("firstName", e.target.value)}
            disabled={!isEditing}
            className="bg-gray-800 border-gray-700 text-white disabled:opacity-50"
          />
        </div>
        <div>
          <Label htmlFor="lastName" className="text-white">Last Name</Label>
          <Input
            id="lastName"
            value={profile.lastName}
            onChange={e => onChange("lastName", e.target.value)}
            disabled={!isEditing}
            className="bg-gray-800 border-gray-700 text-white disabled:opacity-50"
          />
        </div>
      </div>
      <div>
        <Label htmlFor="email" className="text-white">Email</Label>
        <Input
          id="email"
          type="email"
          value={profile.email}
          onChange={e => onChange("email", e.target.value)}
          disabled={!isEditing}
          className="bg-gray-800 border-gray-700 text-white disabled:opacity-50"
        />
        {validationErrors.email && (
          <div className="text-xs text-red-400 mt-1">{validationErrors.email}</div>
        )}
      </div>
      {isEditing ? (
        <>
          <Label htmlFor="phone" className="text-white">Phone</Label>
          <Input
            id="phone"
            value={profile.phone ?? ""}
            onChange={e => onChange("phone", e.target.value)}
            disabled={!isEditing}
            className="bg-gray-800 border-gray-700 text-white disabled:opacity-50"
            placeholder="e.g. +2348012345678"
          />
          {validationErrors.phone && (
            <div className="text-xs text-red-400 mt-1">{validationErrors.phone}</div>
          )}
          <Label htmlFor="gender" className="text-white">Gender</Label>
          <select
            id="gender"
            value={profile.gender ?? ""}
            onChange={e => onChange("gender", e.target.value)}
            disabled={!isEditing}
            className="bg-gray-800 border-gray-700 text-white disabled:opacity-50 rounded-md px-3 py-2 w-full"
          >
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
          </select>
          <Label htmlFor="country" className="text-white">Country</Label>
          <Input
            id="country"
            value={profile.country ?? ""}
            onChange={e => onChange("country", e.target.value)}
            disabled={!isEditing}
            className="bg-gray-800 border-gray-700 text-white disabled:opacity-50"
            placeholder="Country"
          />
        </>
      ) : (
        <>
          <Label htmlFor="phone" className="text-white">Phone</Label>
          <div className="bg-gray-800 border border-gray-700 rounded-md px-3 py-2 text-white mb-2">
            {profile.phone || <span className="text-gray-400">Not set</span>}
          </div>
          <Label htmlFor="gender" className="text-white">Gender</Label>
          <div className="bg-gray-800 border border-gray-700 rounded-md px-3 py-2 text-white mb-2">
            {profile.gender || <span className="text-gray-400">Not set</span>}
          </div>
          <Label htmlFor="country" className="text-white">Country</Label>
          <div className="bg-gray-800 border border-gray-700 rounded-md px-3 py-2 text-white mb-2">
            {profile.country || <span className="text-gray-400">Not set</span>}
          </div>
        </>
      )}
    </CardContent>
  </Card>
); 