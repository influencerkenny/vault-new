"use client"

import { motion } from "framer-motion"
import { Card } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Zap, Shield, Globe, Smartphone, TrendingUp } from "lucide-react"

export function VaultCardShowcase() {
  return (
    <div className="py-20">
      <div className="container mx-auto px-4">
        <motion.div initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} className="text-center mb-12">
          <Badge className="bg-yellow-500/20 text-yellow-400 border-yellow-500/30 mb-4">🚀 LAUNCHING 2026</Badge>
          <h2 className="text-4xl md:text-5xl font-bold mb-4">
            <span className="bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500 bg-clip-text text-transparent">
              Vault Card
            </span>
          </h2>
          <p className="text-xl text-gray-300 max-w-3xl mx-auto">
            The world's first DeFi-native debit card that lets you spend directly from your yield-generating positions
          </p>
        </motion.div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          {/* Card Visual */}
          <motion.div
            initial={{ opacity: 0, x: -50 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: 0.2 }}
            className="relative"
          >
            <div className="relative w-full max-w-md mx-auto">
              {/* Card Background */}
              <div className="relative h-56 rounded-2xl bg-gradient-to-br from-gray-900 via-black to-gray-800 border border-white/20 shadow-2xl overflow-hidden">
                {/* Holographic effect */}
                <div className="absolute inset-0 bg-gradient-to-br from-blue-500/10 via-purple-500/10 to-pink-500/10" />

                {/* Card Content */}
                <div className="relative z-10 p-6 h-full flex flex-col justify-between">
                  <div className="flex justify-between items-start">
                    <div>
                      <div className="text-white font-bold text-lg">VAULT</div>
                      <div className="text-gray-400 text-sm">DeFi Card</div>
                    </div>
                    <div className="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-500" />
                  </div>

                  <div className="space-y-2">
                    <div className="text-white font-mono text-lg tracking-wider">•••• •••• •••• 1234</div>
                    <div className="flex justify-between text-sm">
                      <div>
                        <div className="text-gray-400 text-xs">VALID THRU</div>
                        <div className="text-white">12/29</div>
                      </div>
                      <div>
                        <div className="text-gray-400 text-xs">YIELD APY</div>
                        <div className="text-green-400 font-bold">12.5%</div>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Animated particles */}
                <div className="absolute inset-0 overflow-hidden">
                  {[...Array(20)].map((_, i) => (
                    <motion.div
                      key={i}
                      className="absolute w-1 h-1 bg-blue-400 rounded-full opacity-30"
                      animate={{
                        x: [0, 100, 0],
                        y: [0, -50, 0],
                        opacity: [0.3, 0.8, 0.3],
                      }}
                      transition={{
                        duration: 3 + i * 0.2,
                        repeat: Number.POSITIVE_INFINITY,
                        delay: i * 0.1,
                      }}
                      style={{
                        left: `${Math.random() * 100}%`,
                        top: `${Math.random() * 100}%`,
                      }}
                    />
                  ))}
                </div>
              </div>
            </div>
          </motion.div>

          {/* Features */}
          <motion.div
            initial={{ opacity: 0, x: 50 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: 0.4 }}
            className="space-y-6"
          >
            <div className="grid grid-cols-1 gap-4">
              <Card className="glass p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                    <TrendingUp className="w-5 h-5 text-green-400" />
                  </div>
                  <div>
                    <h3 className="text-white font-semibold">Real-time Yield Spending</h3>
                    <p className="text-gray-400 text-sm">Spend directly from staking rewards without unstaking</p>
                  </div>
                </div>
              </Card>

              <Card className="glass p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                    <Globe className="w-5 h-5 text-blue-400" />
                  </div>
                  <div>
                    <h3 className="text-white font-semibold">Global Acceptance</h3>
                    <p className="text-gray-400 text-sm">Use anywhere Visa/Mastercard is accepted worldwide</p>
                  </div>
                </div>
              </Card>

              <Card className="glass p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center">
                    <Shield className="w-5 h-5 text-purple-400" />
                  </div>
                  <div>
                    <h3 className="text-white font-semibold">Bank-Grade Security</h3>
                    <p className="text-gray-400 text-sm">Multi-sig protection with instant fraud detection</p>
                  </div>
                </div>
              </Card>

              <Card className="glass p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-orange-500/20 rounded-lg flex items-center justify-center">
                    <Zap className="w-5 h-5 text-orange-400" />
                  </div>
                  <div>
                    <h3 className="text-white font-semibold">Instant Settlements</h3>
                    <p className="text-gray-400 text-sm">Sub-second transaction processing with stablecoin rails</p>
                  </div>
                </div>
              </Card>

              <Card className="glass p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-cyan-500/20 rounded-lg flex items-center justify-center">
                    <Smartphone className="w-5 h-5 text-cyan-400" />
                  </div>
                  <div>
                    <h3 className="text-white font-semibold">Mobile-First Experience</h3>
                    <p className="text-gray-400 text-sm">Contactless payments with Apple Pay & Google Pay</p>
                  </div>
                </div>
              </Card>
            </div>

            <div className="bg-gradient-to-r from-yellow-500/10 to-orange-500/10 border border-yellow-500/20 rounded-lg p-4">
              <h4 className="text-yellow-400 font-semibold mb-2">Revolutionary Features:</h4>
              <ul className="text-sm text-gray-300 space-y-1">
                <li>• Automatic yield-to-fiat conversion at point of sale</li>
                <li>• Cashback rewards paid in native VAULT tokens</li>
                <li>• No foreign exchange fees on international transactions</li>
                <li>• ATM fee reimbursements up to $50/month</li>
                <li>• Real-time spending notifications and controls</li>
              </ul>
            </div>
          </motion.div>
        </div>
      </div>
    </div>
  )
}
