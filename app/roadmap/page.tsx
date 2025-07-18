"use client"

import { motion } from "framer-motion"
import { ArrowLeft, Map, Target, Building, TrendingUp } from "lucide-react"
import Link from "next/link"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { FloatingParticles } from "@/components/floating-particles"
import { MorphingBackground } from "@/components/morphing-background"
import { RoadmapTimeline } from "@/components/roadmap-timeline"
import { RoadmapStats } from "@/components/roadmap-stats"
import { VaultCardShowcase } from "@/components/vault-card-showcase"

export default function RoadmapPage() {
  return (
    <div className="min-h-screen bg-black text-white relative overflow-hidden">
      <MorphingBackground />
      <FloatingParticles />

      <div className="relative z-10">
        {/* Header */}
        <header className="border-b border-white/10 backdrop-blur-sm">
          <div className="container mx-auto px-4 py-4">
            <div className="flex items-center justify-between">
              <Link href="/">
                <Button variant="ghost" className="text-white hover:bg-white/10">
                  <ArrowLeft className="w-4 h-4 mr-2" />
                  Back to Home
                </Button>
              </Link>

              <div className="flex items-center gap-2">
                <Map className="w-6 h-6 text-blue-400" />
                <span className="text-xl font-bold">5-Year DeFi Roadmap</span>
              </div>
            </div>
          </div>
        </header>

        {/* Hero Section */}
        <section className="py-20">
          <div className="container mx-auto px-4 text-center">
            <motion.div initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.8 }}>
              <Badge className="bg-blue-500/20 text-blue-400 border-blue-500/30 mb-6">
                2024 - 2029 Strategic Vision
              </Badge>
              <h1 className="text-5xl md:text-7xl font-bold mb-6">
                <span className="bg-gradient-to-r from-blue-400 via-purple-500 to-pink-500 bg-clip-text text-transparent animate-gradient">
                  The Future of DeFi
                </span>
              </h1>
              <p className="text-xl text-gray-300 max-w-4xl mx-auto mb-8">
                Building the world's most comprehensive decentralized finance ecosystem. From institutional-grade
                staking to revolutionary spending solutions, we're creating the infrastructure for the next generation
                of digital finance.
              </p>
              <div className="flex flex-wrap justify-center gap-4 text-sm">
                <Badge variant="outline" className="border-green-500/30 text-green-400">
                  $1B+ TVL Target
                </Badge>
                <Badge variant="outline" className="border-blue-500/30 text-blue-400">
                  15+ Blockchain Support
                </Badge>
                <Badge variant="outline" className="border-purple-500/30 text-purple-400">
                  50+ Protocol Integrations
                </Badge>
                <Badge variant="outline" className="border-yellow-500/30 text-yellow-400">
                  Vault Card 2026
                </Badge>
              </div>
            </motion.div>
          </div>
        </section>

        {/* Stats Section */}
        <section className="py-12">
          <div className="container mx-auto px-4">
            <RoadmapStats />
          </div>
        </section>

        {/* Vision Cards */}
        <section className="py-12">
          <div className="container mx-auto px-4">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
              <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }}>
                <Card className="glass p-6 text-center h-full">
                  <div className="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <Target className="w-6 h-6 text-blue-400" />
                  </div>
                  <h3 className="text-xl font-bold mb-3">Institutional Grade</h3>
                  <p className="text-gray-400">
                    Enterprise-level security, compliance, and scalability designed for traditional finance integration
                    and institutional adoption.
                  </p>
                </Card>
              </motion.div>

              <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3 }}>
                <Card className="glass p-6 text-center h-full">
                  <div className="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <TrendingUp className="w-6 h-6 text-purple-400" />
                  </div>
                  <h3 className="text-xl font-bold mb-3">Yield Optimization</h3>
                  <p className="text-gray-400">
                    AI-powered algorithms continuously optimize yields across multiple protocols, chains, and asset
                    classes for maximum returns.
                  </p>
                </Card>
              </motion.div>

              <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.4 }}>
                <Card className="glass p-6 text-center h-full">
                  <div className="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <Building className="w-6 h-6 text-green-400" />
                  </div>
                  <h3 className="text-xl font-bold mb-3">Real-World Bridge</h3>
                  <p className="text-gray-400">
                    Seamless integration between DeFi yields and traditional spending through innovative card solutions
                    and banking partnerships.
                  </p>
                </Card>
              </motion.div>
            </div>
          </div>
        </section>

        {/* Vault Card Showcase */}
        <VaultCardShowcase />

        {/* Timeline Section */}
        <section className="py-12">
          <div className="container mx-auto px-4">
            <motion.div
              initial={{ opacity: 0, y: 30 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.5 }}
              className="text-center mb-12"
            >
              <h2 className="text-4xl font-bold mb-4">Development Timeline</h2>
              <p className="text-gray-400 max-w-3xl mx-auto">
                Our comprehensive 5-year roadmap outlines the evolution from a staking platform to a complete DeFi
                ecosystem with traditional finance integration. Click on any milestone to explore detailed technical
                specifications and market impact projections.
              </p>
            </motion.div>

            <RoadmapTimeline />
          </div>
        </section>

        {/* Technical Excellence Section */}
        <section className="py-20">
          <div className="container mx-auto px-4">
            <motion.div
              initial={{ opacity: 0, y: 30 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.6 }}
              className="text-center mb-12"
            >
              <h2 className="text-4xl font-bold mb-4">Technical Excellence</h2>
              <p className="text-gray-400 max-w-3xl mx-auto mb-8">
                Built on cutting-edge blockchain technology with institutional-grade security and scalability
              </p>
            </motion.div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              {[
                {
                  title: "Smart Contract Security",
                  description: "Multi-sig, timelock governance, and continuous audits",
                  icon: "🛡️",
                },
                {
                  title: "Cross-Chain Infrastructure",
                  description: "LayerZero, Wormhole, and Axelar integrations",
                  icon: "🌐",
                },
                {
                  title: "AI-Powered Optimization",
                  description: "Machine learning for yield and risk management",
                  icon: "🤖",
                },
                {
                  title: "Regulatory Compliance",
                  description: "KYC/AML, tax reporting, and institutional custody",
                  icon: "📋",
                },
              ].map((item, index) => (
                <motion.div
                  key={index}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: 0.7 + index * 0.1 }}
                >
                  <Card className="glass p-6 text-center h-full">
                    <div className="text-3xl mb-3">{item.icon}</div>
                    <h3 className="text-lg font-bold mb-2 text-white">{item.title}</h3>
                    <p className="text-gray-400 text-sm">{item.description}</p>
                  </Card>
                </motion.div>
              ))}
            </div>
          </div>
        </section>

        {/* CTA Section */}
        <section className="py-20">
          <div className="container mx-auto px-4 text-center">
            <motion.div initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.8 }}>
              <Card className="glass p-8 max-w-3xl mx-auto">
                <h3 className="text-3xl font-bold mb-4">Join the DeFi Revolution</h3>
                <p className="text-gray-300 mb-6">
                  Be part of the future of finance. Start with our current staking platform and grow with us as we build
                  the most comprehensive DeFi ecosystem in the world.
                </p>
                <div className="flex flex-col sm:flex-row gap-4 justify-center">
                  <Link href="/plans">
                    <Button className="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-8 py-3">
                      Start Staking Today
                    </Button>
                  </Link>
                  <Link href="/dashboard">
                    <Button
                      variant="outline"
                      className="border-white/20 text-white hover:bg-white/10 px-8 py-3 bg-transparent"
                    >
                      Explore Dashboard
                    </Button>
                  </Link>
                </div>
                <div className="mt-6 text-sm text-gray-400">
                  <p>🚀 Vault Card Early Access: Sign up for 2026 launch notifications</p>
                </div>
              </Card>
            </motion.div>
          </div>
        </section>
      </div>
    </div>
  )
}
