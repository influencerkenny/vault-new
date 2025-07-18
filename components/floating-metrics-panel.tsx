"use client"

import { motion } from "framer-motion"
import { TrendingUp, Users, DollarSign } from "lucide-react"

interface MetricsPanelProps {
  position: "top-left" | "top-right" | "bottom-left" | "bottom-right"
  delay?: number
}

export function FloatingMetricsPanel({ position, delay = 0 }: MetricsPanelProps) {
  const positions = {
    "top-left": "top-8 left-8",
    "top-right": "top-8 right-8",
    "bottom-left": "bottom-8 left-8",
    "bottom-right": "bottom-8 right-8",
  }

  const metrics = {
    "top-left": { icon: TrendingUp, label: "Yield", value: "12.5%", color: "text-green-400" },
    "top-right": { icon: Users, label: "Stakers", value: "50K+", color: "text-blue-400" },
    "bottom-left": { icon: DollarSign, label: "TVL", value: "$2.4B", color: "text-cyan-400" },
    "bottom-right": { icon: TrendingUp, label: "Growth", value: "+24%", color: "text-purple-400" },
  }

  const metric = metrics[position]
  const Icon = metric.icon

  return (
    <motion.div
      className={`absolute ${positions[position]} w-24 h-20 bg-black/40 backdrop-blur-md rounded-xl border border-white/10 p-3 z-10`}
      initial={{ opacity: 0, scale: 0.8, y: 20 }}
      animate={{ opacity: 1, scale: 1, y: 0 }}
      transition={{ duration: 0.8, delay }}
      whileHover={{ scale: 1.05, y: -2 }}
    >
      <div className="flex flex-col h-full justify-between">
        <Icon className={`w-4 h-4 ${metric.color}`} />
        <div>
          <div className={`text-sm font-bold ${metric.color}`}>{metric.value}</div>
          <div className="text-xs text-gray-400">{metric.label}</div>
        </div>
      </div>
    </motion.div>
  )
}
