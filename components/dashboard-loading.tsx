"use client"

import { motion } from "framer-motion"
import { MorphingBackground } from "@/components/morphing-background"
import { FloatingParticles } from "@/components/floating-particles"

export function DashboardLoading() {
  return (
    <div className="min-h-screen bg-black text-white flex items-center justify-center relative overflow-hidden">
      <MorphingBackground />
      <FloatingParticles />
      <motion.div
        initial={{ scale: 0.8, opacity: 0 }}
        animate={{ scale: 1, opacity: 1 }}
        transition={{ duration: 1, ease: "easeOut" }}
        className="text-center z-10 relative"
      >
        <motion.div
          animate={{ rotate: 360 }}
          transition={{ duration: 2, repeat: Number.POSITIVE_INFINITY, ease: "linear" }}
          className="w-16 h-16 border-4 border-blue-500/20 border-t-blue-500 rounded-full mx-auto mb-6"
        />
        <motion.h2
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.5, duration: 0.8 }}
          className="text-2xl font-bold mb-2 bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent"
        >
          Loading Your Portfolio
        </motion.h2>
        <motion.p
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.7, duration: 0.8 }}
          className="text-gray-400"
        >
          Preparing your financial dashboard...
        </motion.p>
      </motion.div>
    </div>
  )
} 