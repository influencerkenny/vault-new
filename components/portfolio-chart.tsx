"use client"

import { motion } from "framer-motion"
import { Card } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { TrendingUp } from "lucide-react"

export function PortfolioChart() {
  const chartData = [
    { day: "Mon", value: 2400 },
    { day: "Tue", value: 2520 },
    { day: "Wed", value: 2680 },
    { day: "Thu", value: 2750 },
    { day: "Fri", value: 2820 },
    { day: "Sat", value: 2780 },
    { day: "Sun", value: 2847 },
  ]

  const maxValue = Math.max(...chartData.map((d) => d.value))
  const minValue = Math.min(...chartData.map((d) => d.value))

  return (
    <Card className="bg-gray-900/50 border-gray-800/50 backdrop-blur-sm p-6">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h3 className="text-xl font-semibold mb-1">Portfolio Performance</h3>
          <div className="flex items-center space-x-2">
            <TrendingUp className="w-4 h-4 text-green-400" />
            <span className="text-green-400 text-sm font-medium">+12.5% this week</span>
          </div>
        </div>
        <div className="flex space-x-2">
          <Button size="sm" variant="outline" className="text-xs border-gray-600 text-gray-300">
            7D
          </Button>
          <Button size="sm" className="text-xs bg-blue-600 hover:bg-blue-700">
            30D
          </Button>
          <Button size="sm" variant="outline" className="text-xs border-gray-600 text-gray-300">
            90D
          </Button>
        </div>
      </div>

      <div className="relative h-48 mb-4">
        <svg className="w-full h-full" viewBox="0 0 400 200">
          {/* Grid lines */}
          {[0, 1, 2, 3, 4].map((i) => (
            <line key={i} x1="0" y1={i * 50} x2="400" y2={i * 50} stroke="rgba(75, 85, 99, 0.2)" strokeWidth="1" />
          ))}

          {/* Chart line */}
          <motion.path
            d={`M ${chartData.map((d, i) => `${(i / (chartData.length - 1)) * 400},${200 - ((d.value - minValue) / (maxValue - minValue)) * 180}`).join(" L ")}`}
            fill="none"
            stroke="url(#gradient)"
            strokeWidth="3"
            initial={{ pathLength: 0 }}
            animate={{ pathLength: 1 }}
            transition={{ duration: 2, ease: "easeInOut" }}
          />

          {/* Area fill */}
          <motion.path
            d={`M ${chartData.map((d, i) => `${(i / (chartData.length - 1)) * 400},${200 - ((d.value - minValue) / (maxValue - minValue)) * 180}`).join(" L ")} L 400,200 L 0,200 Z`}
            fill="url(#areaGradient)"
            initial={{ pathLength: 0 }}
            animate={{ pathLength: 1 }}
            transition={{ duration: 2, ease: "easeInOut", delay: 0.5 }}
          />

          {/* Data points */}
          {chartData.map((d, i) => (
            <motion.circle
              key={i}
              cx={(i / (chartData.length - 1)) * 400}
              cy={200 - ((d.value - minValue) / (maxValue - minValue)) * 180}
              r="4"
              fill="#3B82F6"
              initial={{ scale: 0 }}
              animate={{ scale: 1 }}
              transition={{ duration: 0.5, delay: 0.8 + i * 0.1 }}
            />
          ))}

          <defs>
            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stopColor="#3B82F6" />
              <stop offset="100%" stopColor="#06B6D4" />
            </linearGradient>
            <linearGradient id="areaGradient" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stopColor="#3B82F6" stopOpacity="0.3" />
              <stop offset="100%" stopColor="#3B82F6" stopOpacity="0.05" />
            </linearGradient>
          </defs>
        </svg>
      </div>

      <div className="flex justify-between text-sm text-gray-400">
        {chartData.map((d, i) => (
          <span key={i}>{d.day}</span>
        ))}
      </div>
    </Card>
  )
}
