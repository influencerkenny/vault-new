import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Lock, Eye, EyeOff, Save } from "lucide-react";
import React from "react";

interface ProfilePasswordFormProps {
  currentPassword: string;
  newPassword: string;
  confirmPassword: string;
  showPassword: boolean;
  isLoading: boolean;
  onChange: (field: string, value: string) => void;
  onToggleShow: () => void;
  onSubmit: () => void;
}

export const ProfilePasswordForm: React.FC<ProfilePasswordFormProps> = ({
  currentPassword,
  newPassword,
  confirmPassword,
  showPassword,
  isLoading,
  onChange,
  onToggleShow,
  onSubmit,
}) => (
  <Card className="bg-gray-900/50 border-gray-800">
    <CardHeader>
      <CardTitle className="text-white flex items-center">
        <Lock className="w-5 h-5 mr-2" />
        Change Password
      </CardTitle>
      <CardDescription className="text-gray-400">
        Update your password to keep your account secure
      </CardDescription>
    </CardHeader>
    <CardContent className="space-y-4">
      <div>
        <Label htmlFor="currentPassword" className="text-white">Current Password</Label>
        <div className="relative">
          <Input
            id="currentPassword"
            type={showPassword ? "text" : "password"}
            value={currentPassword}
            onChange={e => onChange("currentPassword", e.target.value)}
            className="bg-gray-800 border-gray-700 text-white pr-10"
            placeholder="Enter current password"
          />
          <Button
            type="button"
            variant="ghost"
            size="sm"
            className="absolute right-0 top-0 h-full px-3 hover:bg-transparent"
            onClick={onToggleShow}
          >
            {showPassword ? (
              <EyeOff className="w-4 h-4 text-gray-400" />
            ) : (
              <Eye className="w-4 h-4 text-gray-400" />
            )}
          </Button>
        </div>
      </div>
      <div>
        <Label htmlFor="newPassword" className="text-white">New Password</Label>
        <Input
          id="newPassword"
          type="password"
          value={newPassword}
          onChange={e => onChange("newPassword", e.target.value)}
          className="bg-gray-800 border-gray-700 text-white"
          placeholder="Enter new password"
        />
      </div>
      <div>
        <Label htmlFor="confirmPassword" className="text-white">Confirm New Password</Label>
        <Input
          id="confirmPassword"
          type="password"
          value={confirmPassword}
          onChange={e => onChange("confirmPassword", e.target.value)}
          className="bg-gray-800 border-gray-700 text-white"
          placeholder="Confirm new password"
        />
      </div>
      <Button
        onClick={onSubmit}
        disabled={isLoading || !currentPassword || !newPassword || !confirmPassword}
        className="bg-blue-600 hover:bg-blue-700 text-white"
      >
        {isLoading ? (
          <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
        ) : (
          <Save className="w-4 h-4 mr-2" />
        )}
        Update Password
      </Button>
    </CardContent>
  </Card>
); 