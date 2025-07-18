import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import React from "react";

interface ProfileAvatarProps {
  avatar?: string;
  initials: string;
  isEditing: boolean;
  avatarUploading: boolean;
  onAvatarChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
}

export const ProfileAvatar: React.FC<ProfileAvatarProps> = ({
  avatar,
  initials,
  isEditing,
  avatarUploading,
  onAvatarChange,
}) => (
  <Card className="bg-gray-900/50 border-gray-800 h-fit">
    <CardHeader className="pb-4">
      <CardTitle className="text-white text-lg">Profile Photo</CardTitle>
      <CardDescription className="text-gray-400 text-sm">
        Update your profile picture
      </CardDescription>
    </CardHeader>
    <CardContent className="text-center pb-6">
      <div className="relative inline-block">
        <Avatar className="w-20 h-20 lg:w-24 lg:h-24 mx-auto mb-4">
          {avatar ? (
            <AvatarImage
              src={avatar}
              alt="Profile"
              onError={e => {
                (e.target as HTMLImageElement).src = "/placeholder-user.jpg";
              }}
            />
          ) : (
            <AvatarImage src="/placeholder-user.jpg" alt="Profile" />
          )}
          <AvatarFallback className="bg-blue-600 text-white text-lg lg:text-xl">
            {initials}
          </AvatarFallback>
        </Avatar>
        {isEditing && (
          <div className="mt-2 text-center">
            <input type="file" accept="image/*" onChange={onAvatarChange} disabled={avatarUploading} />
            {avatarUploading && <div className="text-xs text-blue-400 mt-1">Uploading...</div>}
          </div>
        )}
      </div>
      <p className="text-xs lg:text-sm text-gray-400 mt-2">
        JPG, PNG or GIF. Max size 2MB.
      </p>
    </CardContent>
  </Card>
); 