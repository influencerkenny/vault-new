"use client"

import { motion } from "framer-motion"
import { Card } from "@/components/ui/card"
import { AnimatedCounter } from "./animated-counter"
import { TrendingUp, Users, DollarSign, Zap, Globe, Shield } from "lucide-react"

const stats = [
  {
    label: "Total Value Locked Target",
    value: 1,
    suffix: "B+",
    color: "text-green-400",
    icon: <DollarSign className="w-5 h-5" />,
    description: "By 2029",
  },
  {
    label: "Supported Blockchains",
    value: 15,
    suffix: "+",
    color: "text-blue-400",
    icon: <Globe className="w-5 h-5" />,
    description: "Multi-chain ecosystem",
  },
  {
    label: "DeFi Protocols Integrated",
    value: 50,
    suffix: "+",
    color: "text-purple-400",
    icon: <Zap className="w-5 h-5" />,
    description: "Yield optimization",
  },
  {
    label: "Expected User Base",
    value: 1,
    suffix: "M+",
    color: "text-orange-400",
    icon: <Users className="w-5 h-5" />,
    description: "Global adoption",
  },
  {
    label: "Security Audits Planned",
    value: 25,
    suffix: "+",
    color: "text-red-400",
    icon: <Shield className="w-5 h-5" />,
    description: "Continuous security",
  },
  {
    label: "Development Milestones",
    value: 45,
    suffix: "",
    color: "text-cyan-400",
    icon: <TrendingUp className="w-5 h-5" />,
    description: "5-year roadmap",
  },
]

export function RoadmapStats() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
      {stats.map((stat, index) => (
        <motion.div
          key={stat.label}
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: index * 0.1 }}
        >
          <Card className="glass p-6">
            <div className="flex items-center gap-3 mb-3">
              <div className={`${stat.color}`}>{stat.icon}</div>
              <div className={`text-2xl font-bold ${stat.color}`}>
                <AnimatedCounter end={stat.value} suffix={stat.suffix} />
              </div>
            </div>
            <h3 className="text-white font-semibold text-sm mb-1">{stat.label}</h3>
            <p className="text-gray-400 text-xs">{stat.description}</p>
          </Card>
        </motion.div>
      ))}
    </div>
  )
}
