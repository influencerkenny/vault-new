"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { apiRequest } from "../../lib/api";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import Link from "next/link";
import { VaultLogo } from "@/components/vault-logo";
import { MorphingBackground } from "@/components/morphing-background";
import { FloatingParticles } from "@/components/floating-particles";

export default function SignupPage() {
  return <div style={{ color: 'white', background: 'black', minHeight: '100vh' }}>Signup Test Page</div>;
}
