import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Calendar, UserCheck, CheckCircle, XCircle } from "lucide-react";
import React from "react";

interface ProfileStatsProps {
  created_at?: string;
  active?: number;
  last_login?: string;
}

export const ProfileStats: React.FC<ProfileStatsProps> = ({ created_at, active, last_login }) => (
  <Card className="bg-gray-900/50 border-gray-800">
    <CardHeader>
      <CardTitle className="text-white">Account Information</CardTitle>
    </CardHeader>
    <CardContent>
      <div className="mb-4">
        <Label className="text-white flex items-center gap-2">
          <Calendar className="w-4 h-4 text-blue-400" /> Member Since
        </Label>
        <div className="bg-gray-800 border border-gray-700 rounded-md px-3 py-2 text-white mb-2 flex items-center gap-2">
          {created_at ? new Date(created_at).toLocaleDateString() : <span className="text-gray-400">Unknown</span>}
          {created_at && <span className="ml-2 px-2 py-0.5 rounded-full bg-blue-600/20 text-blue-400 text-xs">Verified</span>}
        </div>
        <Label className="text-white flex items-center gap-2">
          <UserCheck className="w-4 h-4 text-green-400" /> Active
        </Label>
        <div className="bg-gray-800 border border-gray-700 rounded-md px-3 py-2 text-white mb-2 flex items-center gap-2">
          {active === 1 ? (
            <>
              <CheckCircle className="w-4 h-4 text-green-400" /> <span>Active</span>
            </>
          ) : (
            <>
              <XCircle className="w-4 h-4 text-red-400" /> <span>Inactive</span>
            </>
          )}
        </div>
        <Label className="text-white flex items-center gap-2">
          <Calendar className="w-4 h-4 text-purple-400" /> Last Login
        </Label>
        <div className="bg-gray-800 border border-gray-700 rounded-md px-3 py-2 text-white mb-2 flex items-center gap-2">
          {last_login ? (
            <>
              {new Date(last_login).toLocaleString()} <span className="ml-2 px-2 py-0.5 rounded-full bg-purple-600/20 text-purple-400 text-xs">Recent</span>
            </>
          ) : <span className="text-gray-400">Never</span>}
        </div>
      </div>
    </CardContent>
  </Card>
); 