export type UserStatus = 'Active' | 'Blocked' | 'Suspended';

export interface UserProfile {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  location: string;
  bio: string;
  avatar: string;
  joinDate: string;
  lastLogin: string;
  twoFactorEnabled: boolean;
  emailNotifications: boolean;
  pushNotifications: boolean;
  marketingEmails: boolean;
}

export interface SystemUser {
  id: string;
  first_name: string;
  last_name: string;
  email: string;
  status: UserStatus;
  referral_code?: string;
  join_date: string;
  last_login: string;
  total_balance?: number;
  staked_amount?: number;
  available_balance?: number;
}

export interface UpdateProfileData {
  firstName?: string;
  lastName?: string;
  phone?: string;
  location?: string;
  bio?: string;
  avatar?: string;
}

export interface ChangePasswordData {
  currentPassword: string;
  newPassword: string;
}

export interface UpdateNotificationSettings {
  emailNotifications?: boolean;
  pushNotifications?: boolean;
  marketingEmails?: boolean;
}

export interface UpdateUserStatusData {
  status: UserStatus;
}

export interface DashboardStats {
  totalUsers: number;
  activeUsers: number;
  totalDeposited: number;
  pendingDeposits: number;
  rejectedDeposits: number;
  depositCharges: number;
  totalWithdrawn: number;
  pendingWithdrawals: number;
  rejectedWithdrawals: number;
  withdrawalCharges: number;
  totalPortfolio: number;
  availableBalance: number;
  totalInterest: number;
  activeStakes: number;
  closedStakes: number;
}

export interface RecentActivity {
  id: string;
  type: string;
  user: string;
  amount: number;
  status: string;
  date: string;
}

export interface ChartData {
  date: string;
  deposits: number;
  withdrawals: number;
}

export interface Deposit {
  id: string;
  user_id: string;
  amount: number;
  status: 'pending' | 'approved' | 'rejected';
  payment_method: string;
  charges: number;
  created_at: string;
}

export interface Withdrawal {
  id: string;
  user_id: string;
  amount: number;
  status: 'pending' | 'approved' | 'rejected';
  withdrawal_method: string;
  charges: number;
  created_at: string;
}

export interface StakingPlan {
  id: string;
  name: string;
  description: string;
  min_amount: number;
  max_amount: number;
  interest_rate: number;
  duration_days: number;
  status: 'active' | 'inactive';
}

export interface UserStake {
  id: string;
  user_id: string;
  plan_id: string;
  amount: number;
  interest_earned: number;
  status: 'active' | 'completed' | 'cancelled';
  start_date: string;
  end_date: string;
  plan_name: string;
  interest_rate: number;
}

export async function apiRequest(
  endpoint: string,
  method: 'GET' | 'POST' = 'GET',
  data?: any,
  token?: string
) {
  const headers: any = { 'Content-Type': 'application/json' };
  if (token) headers['Authorization'] = `Bearer ${token}`;
  const API_BASE = process.env.NEXT_PUBLIC_API_BASE || 'http://localhost';
  const res = await fetch(`${API_BASE}/api${endpoint}`, {
    method,
    headers,
    body: data ? JSON.stringify(data) : undefined,
  });
  if (!res.ok) {
    const error = await res.text();
    throw new Error(error || 'API request failed');
  }
  return res.json();
}

class ApiService {
  private baseUrl = '/php-api'; // Updated to use Next.js proxy for PHP backend

  private async request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
    const url = `${this.baseUrl}${endpoint}`;
    const config: RequestInit = {
      headers: {
        'Content-Type': 'application/json',
        ...(options.headers || {}),
      },
      credentials: 'include', // Always include credentials for session
      ...options,
    };
    
    const response = await fetch(url, config);
    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
    }
    return response.json();
  }

  // User Profile Management
  async getUserProfile(): Promise<UserProfile> {
    return this.request<UserProfile>('/user_profile.php');
  }

  async updateProfile(data: UpdateProfileData): Promise<UserProfile> {
    return this.request<UserProfile>('/update_user.php', {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  async changePassword(data: ChangePasswordData): Promise<{ message: string }> {
    return this.request<{ message: string }>('/change_password.php', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async updateNotificationSettings(data: UpdateNotificationSettings): Promise<UserProfile> {
    return this.request<UserProfile>('/update_notifications.php', {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  // System User Management (Admin)
  async getAllUsers(): Promise<SystemUser[]> {
    return this.request<SystemUser[]>('/users.php');
  }

  async getUserById(userId: string): Promise<SystemUser> {
    return this.request<SystemUser>(`/user.php?id=${userId}`);
  }

  async updateUserStatus(userId: string, data: UpdateUserStatusData): Promise<SystemUser> {
    return this.request<SystemUser>(`/update_user.php`, {
      method: 'PUT',
      body: JSON.stringify({ id: userId, ...data }),
    });
  }

  async deleteUser(userId: string): Promise<{ message: string }> {
    return this.request<{ message: string }>('/delete_user.php', {
      method: 'DELETE',
      body: JSON.stringify({ id: userId }),
    });
  }

  // Admin Dashboard Data
  async getDashboardStats(): Promise<DashboardStats> {
    return this.request<DashboardStats>('/dashboard_stats.php');
  }

  async getRecentActivity(): Promise<RecentActivity[]> {
    return this.request<RecentActivity[]>('/recent_activity.php');
  }

  async getChartData(): Promise<ChartData[]> {
    return this.request<ChartData[]>('/chart_data.php');
  }

  // Deposits Management
  async getAllDeposits(): Promise<Deposit[]> {
    return this.request<Deposit[]>('/deposits.php');
  }

  async updateDepositStatus(depositId: string, status: 'approved' | 'rejected'): Promise<{ message: string }> {
    return this.request<{ message: string }>('/update_deposit.php', {
      method: 'PUT',
      body: JSON.stringify({ id: depositId, status }),
    });
  }

  // Withdrawals Management
  async getAllWithdrawals(): Promise<Withdrawal[]> {
    return this.request<Withdrawal[]>('/withdrawals.php');
  }

  async updateWithdrawalStatus(withdrawalId: string, status: 'approved' | 'rejected'): Promise<{ message: string }> {
    return this.request<{ message: string }>('/update_withdrawal.php', {
      method: 'PUT',
      body: JSON.stringify({ id: withdrawalId, status }),
    });
  }

  // Staking Management
  async getStakingPlans(): Promise<StakingPlan[]> {
    return this.request<StakingPlan[]>('/staking_plans.php');
  }

  async getUserStakes(userId: string): Promise<UserStake[]> {
    return this.request<UserStake[]>(`/user_stakes.php?user_id=${userId}`);
  }

  async createStake(userId: string, planId: string, amount: number): Promise<{ message: string }> {
    return this.request<{ message: string }>('/create_stake.php', {
      method: 'POST',
      body: JSON.stringify({ user_id: userId, plan_id: planId, amount }),
    });
  }

  // Authentication
  async adminLogin(username: string, password: string): Promise<SystemUser> {
    return this.request<SystemUser>('/admin_login.php', {
      method: 'POST',
      body: JSON.stringify({ username, password }),
    });
  }

  async userLogin(email: string, password: string): Promise<SystemUser> {
    return this.request<SystemUser>('/user_login.php', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
  }
}

export const userApi = new ApiService(); 