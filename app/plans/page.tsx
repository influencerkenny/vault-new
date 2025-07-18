"use client"

import { motion } from "framer-motion"
import { Card } from "@/components/ui/card"
import Link from "next/link"
import { ArrowLeft, Star, Zap, Shield, Crown } from "lucide-react"
import { VaultLogo } from "@/components/vault-logo"
import { WalletConnection } from "@/components/wallet-connection"
import { StakingPlanCard } from "@/components/staking-plan-card"
import { StakingStats } from "@/components/staking-stats"
import { MorphingBackground } from "@/components/morphing-background"
import { FloatingParticles } from "@/components/floating-particles"
import { useEffect, useState } from "react";

export default function StakingPlans() {
  const [plans, setPlans] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch("/vault-new/api/plans/list.php")
      .then(res => res.json())
      .then(data => {
        console.log("Fetched plans:", data);
        setPlans(data);
      })
      .finally(() => setLoading(false));
  }, []);

  const handlePlanSelect = (planId: string) => {
    console.log("Selected plan:", planId);
    // Handle plan selection logic here
  };

  if (loading) return <div>Loading...</div>;

  return (
    <div className="min-h-screen bg-black text-white">
      <MorphingBackground />
      <FloatingParticles />
      {/* Header */}
      <motion.header
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.8 }}
        className="relative z-50 px-6 py-6 border-b border-gray-800/50 backdrop-blur-xl"
      >
        <nav className="flex items-center justify-between max-w-7xl mx-auto">
          <div className="flex items-center space-x-6">
            <Link href="/">
              <motion.div
                className="flex items-center space-x-3"
                whileHover={{ scale: 1.02 }}
                transition={{ type: "spring", stiffness: 400, damping: 25 }}
              >
                <VaultLogo width={240} height={65} className="h-16 w-auto" />
              </motion.div>
            </Link>
            <div className="hidden md:flex items-center space-x-6">
              <Link href="/" className="flex items-center space-x-2 text-gray-400 hover:text-white transition-colors">
                <ArrowLeft className="w-4 h-4" />
                <span className="text-sm">Back to Home</span>
              </Link>
            </div>
          </div>
          <WalletConnection />
        </nav>
      </motion.header>
      {/* Main Content */}
      <main className="relative z-10 px-6 py-12">
        <div className="max-w-7xl mx-auto">
          {/* Page Header */}
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
            className="text-center mb-12"
          >
            <h1 className="text-4xl lg:text-5xl font-bold mb-4">
              Vault{" "}
              <span className="bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">Staking</span>{" "}
              Plans
            </h1>
            <p className="text-xl text-gray-400 max-w-2xl mx-auto">
              Choose your staking plan and start earning daily rewards with institutional-grade security
            </p>
          </motion.div>
          {/* Stats Section */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8, delay: 0.2 }}
            className="mb-12"
          >
            <StakingStats />
          </motion.div>
          {/* Staking Plans */}
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8, delay: 0.4 }}
            className="mb-12"
          >
            <div className="text-center mb-8">
              <h2 className="text-3xl font-bold mb-4">Choose Your Staking Plan</h2>
              <p className="text-gray-400">Select the plan that best fits your investment strategy</p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              {plans.length === 0 ? (
                <div className="col-span-3 text-center text-gray-400">No plans available.</div>
              ) : (
                plans.map((plan: any, index: number) => (
                  <motion.div
                    key={plan.id || index}
                    initial={{ opacity: 0, y: 30 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.6, delay: 0.6 + index * 0.1 }}
                  >
                    <StakingPlanCard plan={plan} onSelect={handlePlanSelect} />
                  </motion.div>
                ))
              )}
            </div>
          </motion.div>
          {/* Additional Info */}
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8, delay: 0.8 }}
            className="grid grid-cols-1 md:grid-cols-2 gap-8"
          >
            <Card className="bg-gray-900/50 border-gray-800/50 backdrop-blur-sm p-6">
              <div className="flex items-center space-x-3 mb-4">
                <Shield className="w-6 h-6 text-blue-400" />
                <h3 className="text-xl font-semibold">Security First</h3>
              </div>
              <p className="text-gray-400 leading-relaxed">
                Your assets are protected by multi-signature wallets, insurance coverage, and institutional-grade
                security protocols. We prioritize the safety of your investments above all else.
              </p>
            </Card>
            <Card className="bg-gray-900/50 border-gray-800/50 backdrop-blur-sm p-6">
              <div className="flex items-center space-x-3 mb-4">
                <Zap className="w-6 h-6 text-cyan-400" />
                <h3 className="text-xl font-semibold">Instant Rewards</h3>
              </div>
              <p className="text-gray-400 leading-relaxed">
                Earn daily rewards that are automatically distributed to your wallet. Watch your SOL grow with our
                optimized staking algorithms and compound interest features.
              </p>
            </Card>
          </motion.div>
        </div>
      </main>
    </div>
  );
}
ddddd
