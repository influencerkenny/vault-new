"use client";

import { useState, useEffect, useRef } from "react";
import { apiRequest } from "../../lib/api";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import Link from "next/link";
import { VaultLogo } from "@/components/vault-logo";
import { MorphingBackground } from "@/components/morphing-background";
import { FloatingParticles } from "@/components/floating-particles";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { useRouter, useSearchParams } from "next/navigation";
import { toast } from "@/components/ui/use-toast";
import { Eye, EyeOff, Loader2 } from "lucide-react";

// Static country list (full ISO 3166-1 list)
const countries = [
  "Afghanistan", "Albania", "Algeria", "Andorra", "Angola", "Antigua and Barbuda", "Argentina", "Armenia", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bhutan", "Bolivia", "Bosnia and Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria", "Burkina Faso", "Burundi", "Cabo Verde", "Cambodia", "Cameroon", "Canada", "Central African Republic", "Chad", "Chile", "China", "Colombia", "Comoros", "Congo (Congo-Brazzaville)", "Costa Rica", "Croatia", "Cuba", "Cyprus", "Czechia (Czech Republic)", "Democratic Republic of the Congo", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Eswatini (fmr. Swaziland)", "Ethiopia", "Fiji", "Finland", "France", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Greece", "Grenada", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Holy See", "Honduras", "Hungary", "Iceland", "India", "Indonesia", "Iran", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Kuwait", "Kyrgyzstan", "Laos", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libya", "Liechtenstein", "Lithuania", "Luxembourg", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Mauritania", "Mauritius", "Mexico", "Micronesia", "Moldova", "Monaco", "Mongolia", "Montenegro", "Morocco", "Mozambique", "Myanmar (formerly Burma)", "Namibia", "Nauru", "Nepal", "Netherlands", "New Zealand", "Nicaragua", "Niger", "Nigeria", "North Korea", "North Macedonia", "Norway", "Oman", "Pakistan", "Palau", "Palestine State", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Poland", "Portugal", "Qatar", "Romania", "Russia", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Serbia", "Seychelles", "Sierra Leone", "Singapore", "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Korea", "South Sudan", "Spain", "Sri Lanka", "Sudan", "Suriname", "Sweden", "Switzerland", "Syria", "Tajikistan", "Tanzania", "Thailand", "Timor-Leste", "Togo", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States of America", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Yemen", "Zambia", "Zimbabwe"
];

// Password validation rules
const passwordRules = [
  { test: (pw: string) => pw.length >= 8, message: "At least 8 characters" },
  { test: (pw: string) => /[A-Z]/.test(pw), message: "At least one uppercase letter" },
  { test: (pw: string) => /[a-z]/.test(pw), message: "At least one lowercase letter" },
  { test: (pw: string) => /[0-9]/.test(pw), message: "At least one number" },
  { test: (pw: string) => /[^A-Za-z0-9]/.test(pw), message: "At least one special character" },
];

// Email regex
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
// Basic international phone regex
const phoneRegex = /^\+?[0-9]{7,15}$/;

export default function SignupPage() {
  const [form, setForm] = useState({
    username: "",
    first_name: "",
    last_name: "",
    email: "",
    password: "",
    phone: "",
    country: "",
    referred_by: ""
  });
  const [errors, setErrors] = useState<{ [k: string]: string }>({});
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);
  const [showModal, setShowModal] = useState(false);
  const [recaptchaToken, setRecaptchaToken] = useState("");
  const [countrySearch, setCountrySearch] = useState("");
  const [countryDropdownOpen, setCountryDropdownOpen] = useState(false);
  const router = useRouter();
  const searchParams = useSearchParams();
  const fieldRefs = {
    username: useRef<HTMLInputElement>(null),
    first_name: useRef<HTMLInputElement>(null),
    last_name: useRef<HTMLInputElement>(null),
    email: useRef<HTMLInputElement>(null),
    password: useRef<HTMLInputElement>(null),
    phone: useRef<HTMLInputElement>(null),
    country: useRef<HTMLInputElement>(null),
    referred_by: useRef<HTMLInputElement>(null),
  };

  // Autofill referral code from URL
  useEffect(() => {
    const ref = searchParams.get("ref");
    if (ref) setForm((f) => ({ ...f, referred_by: ref }));
  }, [searchParams]);

  // Show modal and redirect after signup
  useEffect(() => {
    if (success) {
      setShowModal(true);
      toast({ title: "Signup successful!", description: "Welcome to Vault. Redirecting to sign in..." });
      const timer = setTimeout(() => {
        setShowModal(false);
        router.push("/signin");
      }, 3000);
      return () => clearTimeout(timer);
    }
  }, [success, router]);

  // reCAPTCHA integration (Google v2, invisible)
  useEffect(() => {
    if (!window.grecaptcha) {
      const script = document.createElement("script");
      script.src = "https://www.google.com/recaptcha/api.js?render=explicit";
      script.async = true;
      document.body.appendChild(script);
    }
  }, []);

  // Validate all fields
  const validate = () => {
    const newErrors: { [k: string]: string } = {};
    if (!form.username) newErrors.username = "Username is required";
    if (!form.first_name) newErrors.first_name = "First name is required";
    if (!form.last_name) newErrors.last_name = "Last name is required";
    if (!form.email) newErrors.email = "Email is required";
    else if (!emailRegex.test(form.email)) newErrors.email = "Invalid email format";
    if (!form.password) newErrors.password = "Password is required";
    else {
      for (const rule of passwordRules) {
        if (!rule.test(form.password)) {
          newErrors.password = rule.message;
          break;
        }
      }
    }
    if (form.phone && !phoneRegex.test(form.phone)) newErrors.phone = "Invalid phone number";
    if (!form.country) newErrors.country = "Country is required";
    // No validation for referred_by (optional)
    // reCAPTCHA is now optional
    return newErrors;
  };

  // Focus first invalid field
  useEffect(() => {
    const firstError = Object.keys(errors)[0];
    if (firstError && fieldRefs[firstError as keyof typeof fieldRefs]?.current) {
      fieldRefs[firstError as keyof typeof fieldRefs].current?.focus();
    }
  }, [errors]);

  // Handle reCAPTCHA
  const handleRecaptcha = (cb: () => void) => {
    if (window.grecaptcha) {
      window.grecaptcha.ready(() => {
        window.grecaptcha.execute("YOUR_RECAPTCHA_SITE_KEY", { action: "submit" }).then((token: string) => {
          setRecaptchaToken(token);
          cb();
        });
      });
    } else {
      setErrors((e) => ({ ...e, recaptcha: "reCAPTCHA failed to load" }));
    }
  };

  // Handle form submit
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});
    setLoading(true);
    // No longer call handleRecaptcha for now
    const validationErrors = validate();
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      setLoading(false);
      return;
    }
    try {
      await apiRequest("/register", "POST", {
        username: form.username,
        first_name: form.first_name,
        last_name: form.last_name,
        email: form.email,
        password: form.password,
        phone: form.phone,
        country: form.country,
        referred_by: form.referred_by || null,
      });
      setSuccess(true);
      setForm({
        username: "",
        first_name: "",
        last_name: "",
        email: "",
        password: "",
        phone: "",
        country: "",
        referred_by: ""
      });
      setRecaptchaToken("");
    } catch (err: any) {
      toast({ title: "Signup failed", description: err.message || "Registration failed. Please try again.", variant: "destructive" });
    } finally {
      setLoading(false);
    }
  };

  // Country dropdown filtering
  const filteredCountries = countries.filter((c) => c.toLowerCase().includes(countrySearch.toLowerCase()));

  return (
    <div className="min-h-screen bg-black text-white flex items-center justify-center">
      <div className="w-full max-w-md">
        <Card className="bg-gray-900/50 border-gray-800/50 backdrop-blur-sm p-8">
          <div className="text-center mb-8">
            <h1 className="text-3xl font-bold mb-2">Create Account</h1>
            <p className="text-gray-400">Join Vault and start earning today</p>
          </div>
          <form className="space-y-4" onSubmit={handleSubmit} aria-label="Signup form">
            <div className="space-y-2">
              <Label htmlFor="username" className="text-sm font-medium text-gray-300">
                Username
              </Label>
              <Input
                id="username"
                name="username"
                type="text"
                value={form.username ?? ""}
                onChange={e => setForm({ ...form, username: e.target.value })}
                placeholder="Choose a username"
                required
                aria-invalid={!!errors.username}
                aria-describedby={errors.username ? "username-error" : undefined}
                ref={fieldRefs.username}
              />
              {errors.username && <div id="username-error" className="text-red-400 text-xs mt-1" role="alert">{errors.username}</div>}
            </div>
            <div className="flex gap-4">
              <div className="space-y-2 w-1/2">
                <Label htmlFor="first_name" className="text-sm font-medium text-gray-300">
                  First Name
                </Label>
                <Input
                  id="first_name"
                  name="first_name"
                  type="text"
                  value={form.first_name ?? ""}
                  onChange={e => setForm({ ...form, first_name: e.target.value })}
                  placeholder="First name"
                  required
                  aria-invalid={!!errors.first_name}
                  aria-describedby={errors.first_name ? "first_name-error" : undefined}
                  ref={fieldRefs.first_name}
                />
                {errors.first_name && <div id="first_name-error" className="text-red-400 text-xs mt-1" role="alert">{errors.first_name}</div>}
              </div>
              <div className="space-y-2 w-1/2">
                <Label htmlFor="last_name" className="text-sm font-medium text-gray-300">
                  Last Name
                </Label>
                <Input
                  id="last_name"
                  name="last_name"
                  type="text"
                  value={form.last_name ?? ""}
                  onChange={e => setForm({ ...form, last_name: e.target.value })}
                  placeholder="Last name"
                  required
                  aria-invalid={!!errors.last_name}
                  aria-describedby={errors.last_name ? "last_name-error" : undefined}
                  ref={fieldRefs.last_name}
                />
                {errors.last_name && <div id="last_name-error" className="text-red-400 text-xs mt-1" role="alert">{errors.last_name}</div>}
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="email" className="text-sm font-medium text-gray-300">
                Email Address
              </Label>
              <Input
                id="email"
                name="email"
                type="email"
                value={form.email ?? ""}
                onChange={e => setForm({ ...form, email: e.target.value })}
                placeholder="Enter your email"
                required
                aria-invalid={!!errors.email}
                aria-describedby={errors.email ? "email-error" : undefined}
                ref={fieldRefs.email}
              />
              {errors.email && <div id="email-error" className="text-red-400 text-xs mt-1" role="alert">{errors.email}</div>}
            </div>
            <div className="space-y-2 relative">
              <Label htmlFor="password" className="text-sm font-medium text-gray-300">
                Password
              </Label>
              <div className="relative">
                <Input
                  id="password"
                  name="password"
                  type={showPassword ? "text" : "password"}
                  value={form.password ?? ""}
                  onChange={e => setForm({ ...form, password: e.target.value })}
                  placeholder="Create a password"
                  required
                  aria-invalid={!!errors.password}
                  aria-describedby={errors.password ? "password-error" : undefined}
                  ref={fieldRefs.password}
                  autoComplete="new-password"
                />
                <button
                  type="button"
                  tabIndex={0}
                  aria-label={showPassword ? "Hide password" : "Show password"}
                  onClick={() => setShowPassword((v) => !v)}
                  className="absolute right-2 top-2 text-gray-400 hover:text-gray-200 focus:outline-none"
                >
                  {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                </button>
              </div>
              {errors.password && <div id="password-error" className="text-red-400 text-xs mt-1" role="alert">{errors.password}</div>}
              <ul className="text-xs text-gray-400 mt-1 space-y-0.5">
                {passwordRules.map((rule, i) => (
                  <li key={i} className={rule.test(form.password) ? "text-green-400" : ""}>
                    {rule.message}
                  </li>
                ))}
              </ul>
            </div>
            <div className="space-y-2">
              <Label htmlFor="phone" className="text-sm font-medium text-gray-300">
                Phone
              </Label>
              <Input
                id="phone"
                name="phone"
                type="text"
                value={form.phone ?? ""}
                onChange={e => setForm({ ...form, phone: e.target.value })}
                placeholder="Phone number"
                aria-invalid={!!errors.phone}
                aria-describedby={errors.phone ? "phone-error" : undefined}
                ref={fieldRefs.phone}
              />
              {errors.phone && <div id="phone-error" className="text-red-400 text-xs mt-1" role="alert">{errors.phone}</div>}
            </div>
            <div className="space-y-2 relative">
              <Label htmlFor="country" className="text-sm font-medium text-gray-300">
                Country
              </Label>
              <Input
                id="country"
                name="country"
                type="text"
                value={form.country ?? ""}
                onChange={e => {
                  setForm({ ...form, country: e.target.value });
                  setCountrySearch(e.target.value);
                  setCountryDropdownOpen(true);
                }}
                placeholder="Select country"
                autoComplete="off"
                aria-invalid={!!errors.country}
                aria-describedby={errors.country ? "country-error" : undefined}
                ref={fieldRefs.country}
                onFocus={() => setCountryDropdownOpen(true)}
                onBlur={() => setTimeout(() => setCountryDropdownOpen(false), 150)}
              />
              {countryDropdownOpen && filteredCountries.length > 0 && (
                <ul className="absolute z-10 bg-gray-800 border border-gray-700 w-full mt-1 max-h-40 overflow-y-auto rounded shadow-lg">
                  {filteredCountries.map((c) => (
                    <li
                      key={c}
                      tabIndex={0}
                      className="px-3 py-2 hover:bg-blue-600 cursor-pointer text-sm"
                      onMouseDown={() => {
                        setForm({ ...form, country: c });
                        setCountrySearch("");
                        setCountryDropdownOpen(false);
                      }}
                      onKeyDown={e => {
                        if (e.key === "Enter" || e.key === " ") {
                          setForm({ ...form, country: c });
                          setCountrySearch("");
                          setCountryDropdownOpen(false);
                        }
                      }}
                    >
                      {c}
                    </li>
                  ))}
                </ul>
              )}
              {errors.country && <div id="country-error" className="text-red-400 text-xs mt-1" role="alert">{errors.country}</div>}
            </div>
            <div className="space-y-2">
              <Label htmlFor="referred_by" className="text-sm font-medium text-gray-300">
                Referred By (User ID)
              </Label>
              <Input
                id="referred_by"
                name="referred_by"
                type="text"
                value={form.referred_by ?? ""}
                onChange={e => setForm({ ...form, referred_by: e.target.value })}
                placeholder="User ID of referrer (optional)"
                ref={fieldRefs.referred_by}
              />
            </div>
            {/* reCAPTCHA error */}
            {errors.recaptcha && <div className="text-red-400 text-xs mt-1" role="alert">{errors.recaptcha}</div>}
            <Button
              type="submit"
              className="w-full bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white py-3 rounded-lg font-medium transition-all duration-300 flex items-center justify-center"
              disabled={loading}
              aria-busy={loading}
            >
              {loading && <Loader2 className="animate-spin mr-2 h-5 w-5" />}
              Sign Up
            </Button>
          </form>
          <div className="mt-8 text-center">
            <p className="text-gray-400 text-sm">
              Already have an account?{' '}
              <Link href="/signin" className="text-blue-400 hover:text-blue-300 transition-colors font-medium">
                Sign in
              </Link>
            </p>
          </div>
        </Card>
        {/* Success Modal */}
        <Dialog open={showModal} onOpenChange={setShowModal}>
          <DialogContent aria-modal="true" role="dialog">
            <DialogHeader>
              <DialogTitle>Congratulations!</DialogTitle>
              <DialogDescription>
                Your account has been created. Redirecting to sign in...
              </DialogDescription>
            </DialogHeader>
          </DialogContent>
        </Dialog>
      </div>
    </div>
  );
}

// For TypeScript: declare grecaptcha on window
declare global {
  interface Window {
    grecaptcha: any;
  }
}
