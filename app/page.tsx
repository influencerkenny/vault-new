"use client"

import { motion, useReducedMotion, useScroll, useTransform } from "framer-motion"
import { Button } from "@/components/ui/button"
import Link from "next/link"
import { ArrowRight, TrendingUp, Shield, Sparkles, Zap } from "lucide-react"
import { VaultLogo } from "@/components/vault-logo"
import { AnimatedCounter } from "@/components/animated-counter"
import { FloatingParticles } from "@/components/floating-particles"
import { MorphingBackground } from "@/components/morphing-background"
import { InteractiveCard } from "@/components/interactive-card"
import { CleanDataVisualization } from "@/components/clean-data-visualization"
import { useRef } from "react"

export default function VaultLanding() {
  const shouldReduceMotion = useReducedMotion()
  const containerRef = useRef<HTMLDivElement>(null)
  const { scrollYProgress } = useScroll({
    target: containerRef,
    offset: ["start start", "end start"],
  })

  const backgroundY = useTransform(scrollYProgress, [0, 1], ["0%", "50%"])
  const textY = useTransform(scrollYProgress, [0, 1], ["0%", "30%"])

  const fadeInUp = {
    initial: { opacity: 0, y: 20 },
    animate: { opacity: 1, y: 0 },
    transition: { duration: 0.6, ease: [0.21, 1.11, 0.81, 0.99] },
  }

  const staggerContainer = {
    animate: {
      transition: {
        staggerChildren: 0.1,
      },
    },
  }

  const letterAnimation = {
    initial: { opacity: 0, y: 50 },
    animate: { opacity: 1, y: 0 },
  }

  const titleText = "Your wealth, amplified"
  const words = titleText.split(" ")

  return (
    <div ref={containerRef} className="min-h-screen bg-black text-white overflow-hidden">
      <MorphingBackground />
      <FloatingParticles />

      {/* Header */}
      <motion.header
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.8, ease: "easeOut" }}
        className="relative z-50 px-6 py-6 border-b border-gray-800/50 backdrop-blur-xl"
      >
        <nav className="flex items-center justify-between max-w-7xl mx-auto">
          {/* Logo */}
          <motion.div
            className="flex items-center space-x-3"
            whileHover={{ scale: 1.02 }}
            transition={{ type: "spring", stiffness: 400, damping: 25 }}
          >
            <VaultLogo width={200} height={55} className="h-14 w-auto" priority />
          </motion.div>

          {/* Navigation */}
          <div className="hidden md:flex items-center space-x-8">
            {["Platform", "Solutions", "Why Us", "FAQ"].map((item, index) => (
              <motion.div
                key={item}
                initial={{ opacity: 0, y: -10 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6, delay: index * 0.1 }}
                whileHover={{ scale: 1.05 }}
              >
                <Link
                  href={item === "Solutions" ? "/plans" : "http://localhost/vault-new/signup.php"}
                  className="text-gray-400 hover:text-white transition-all duration-300 font-medium text-sm tracking-wide relative group"
                >
                  {item}
                  <motion.div className="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-blue-400 to-cyan-400 group-hover:w-full transition-all duration-300" />
                </Link>
              </motion.div>
            ))}
            <div className="flex items-center space-x-4">
              <Link href="http://localhost/vault-new/signin.php">
                <motion.div
                  initial={{ opacity: 0, scale: 0.9 }}
                  animate={{ opacity: 1, scale: 1 }}
                  transition={{ duration: 0.6, delay: 0.3 }}
                  whileHover={{ scale: 1.05 }}
                  whileTap={{ scale: 0.95 }}
                >
                  <Button
                    variant="ghost"
                    className="text-gray-400 hover:text-white hover:bg-gray-800/50 transition-all duration-300 rounded-full px-6 font-medium"
                  >
                    Sign In
                  </Button>
                </motion.div>
              </Link>
              <Link href="http://localhost/vault-new/signup.php">
                <motion.div
                  initial={{ opacity: 0, scale: 0.9 }}
                  animate={{ opacity: 1, scale: 1 }}
                  transition={{ duration: 0.6, delay: 0.4 }}
                  whileHover={{ scale: 1.05 }}
                  whileTap={{ scale: 0.95 }}
                >
                  <Button
                    variant="outline"
                    className="bg-transparent border-blue-500/30 text-blue-400 hover:bg-blue-500/10 hover:border-blue-400 hover:text-blue-300 transition-all duration-300 rounded-full px-6 font-medium relative overflow-hidden group"
                  >
                    <motion.div className="absolute inset-0 bg-gradient-to-r from-blue-500/20 to-cyan-400/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                    <span className="relative z-10">Get Started</span>
                  </Button>
                </motion.div>
              </Link>
            </div>
          </div>

          {/* Mobile menu */}
          <div className="md:hidden">
            <motion.div whileTap={{ scale: 0.95 }}>
              <Button variant="ghost" size="sm" className="text-white">
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                </svg>
              </Button>
            </motion.div>
          </div>
        </nav>
      </motion.header>

      {/* Hero Section */}
      <section className="relative px-6 py-20 lg:py-32">
        <motion.div style={{ y: textY }} className="max-w-7xl mx-auto">
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            {/* Left Content */}
            <motion.div className="space-y-8" variants={staggerContainer} initial="initial" animate="animate">
              <motion.div variants={fadeInUp} className="space-y-6">
                <motion.div
                  className="inline-flex items-center space-x-2 bg-blue-500/10 border border-blue-500/20 rounded-full px-4 py-2 text-sm text-blue-400 relative overflow-hidden group"
                  whileHover={{ scale: 1.05 }}
                  initial={{ opacity: 0, x: -20 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ duration: 0.8, delay: 0.2 }}
                >
                  <motion.div className="absolute inset-0 bg-gradient-to-r from-blue-500/20 to-cyan-400/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                  <Sparkles className="w-4 h-4 relative z-10" />
                  <span className="relative z-10">Revolutionizing DeFi Staking</span>
                </motion.div>

                <motion.h1 className="text-5xl lg:text-7xl font-bold leading-tight tracking-tight">
                  {words.map((word, wordIndex) => (
                    <motion.span
                      key={wordIndex}
                      className="inline-block mr-4"
                      initial={{ opacity: 0, y: 50 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{
                        duration: 0.8,
                        delay: wordIndex * 0.2,
                        ease: [0.21, 1.11, 0.81, 0.99],
                      }}
                    >
                      {word === "amplified" ? (
                        <span className="bg-gradient-to-r from-blue-400 via-blue-500 to-cyan-400 bg-clip-text text-transparent">
                          {word}
                        </span>
                      ) : (
                        word
                      )}
                    </motion.span>
                  ))}
                </motion.h1>

                <motion.p
                  variants={fadeInUp}
                  className="text-xl text-gray-400 leading-relaxed max-w-lg font-light"
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.8, delay: 0.8 }}
                >
                  Experience the future of staking with institutional-grade security, optimized yields, and seamless
                  wallet integration. Built for the next generation of crypto investors.
                </motion.p>
              </motion.div>

              <motion.div
                variants={fadeInUp}
                className="flex flex-col sm:flex-row gap-4"
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.8, delay: 1.0 }}
              >
                <Link href="http://localhost/vault-new/signup.php">
                  <motion.div whileHover={{ scale: 1.05 }} whileTap={{ scale: 0.95 }}>
                    <Button
                      size="lg"
                      className="bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white px-8 py-4 text-lg font-semibold rounded-full shadow-2xl shadow-blue-500/25 hover:shadow-blue-500/40 transition-all duration-300 group border-0 relative overflow-hidden"
                    >
                      <motion.div className="absolute inset-0 bg-gradient-to-r from-cyan-400/20 to-blue-400/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                      <span className="relative z-10">Start Earning</span>
                      <ArrowRight className="ml-2 h-5 w-5 group-hover:translate-x-1 transition-transform duration-200 relative z-10" />
                    </Button>
                  </motion.div>
                </Link>
                <motion.div whileHover={{ scale: 1.05 }} whileTap={{ scale: 0.95 }}>
                  <Button
                    variant="outline"
                    size="lg"
                    className="bg-transparent border-gray-700 text-gray-300 hover:bg-gray-800/50 hover:border-gray-600 hover:text-white px-8 py-4 text-lg font-medium rounded-full transition-all duration-300 relative overflow-hidden group"
                  >
                    <motion.div className="absolute inset-0 bg-gradient-to-r from-gray-800/20 to-gray-700/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                    <span className="relative z-10">Learn More</span>
                  </Button>
                </motion.div>
              </motion.div>

              {/* Stats */}
              <motion.div
                variants={fadeInUp}
                className="grid grid-cols-3 gap-8 pt-8 border-t border-gray-800/50"
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.8, delay: 1.2 }}
              >
                {[
                  { value: 2.4, label: "Total Value Locked", prefix: "$", suffix: "B+" },
                  { value: 12.5, label: "Average APY", suffix: "%", decimals: 1 },
                  { value: 50, label: "Active Stakers", suffix: "K+" },
                ].map((stat, index) => (
                  <motion.div
                    key={index}
                    className="text-center group"
                    whileHover={{ scale: 1.05 }}
                    transition={{ type: "spring", stiffness: 300, damping: 20 }}
                  >
                    <div className="text-2xl font-bold text-blue-400 group-hover:text-cyan-400 transition-colors duration-300">
                      <AnimatedCounter
                        end={stat.value}
                        prefix={stat.prefix}
                        suffix={stat.suffix}
                        decimals={stat.decimals || 0}
                        duration={2.5}
                      />
                    </div>
                    <div className="text-sm text-gray-500 mt-1 group-hover:text-gray-400 transition-colors duration-300">
                      {stat.label}
                    </div>
                  </motion.div>
                ))}
              </motion.div>
            </motion.div>

            {/* Clean Right Visual */}
            <motion.div
              className="relative flex justify-center items-center order-first lg:order-last"
              initial={{ opacity: 0, scale: 0.8 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ duration: 1, ease: "easeOut", delay: 0.2 }}
              style={{ y: backgroundY }}
            >
              <CleanDataVisualization />
            </motion.div>
          </div>
        </motion.div>
      </section>

      {/* Features Section */}
      <section className="px-6 py-20 relative">
        <div className="max-w-7xl mx-auto">
          <motion.div
            className="text-center mb-16"
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
            viewport={{ once: true }}
          >
            <motion.h2
              className="text-4xl lg:text-5xl font-bold mb-6"
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8, delay: 0.2 }}
              viewport={{ once: true }}
            >
              Built for{" "}
              <motion.span
                className="bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent"
                initial={{ backgroundPosition: "0% 50%" }}
                animate={{ backgroundPosition: "100% 50%" }}
                transition={{ duration: 3, repeat: Number.POSITIVE_INFINITY, repeatType: "reverse" }}
              >
                tomorrow's
              </motion.span>{" "}
              investors
            </motion.h2>
            <motion.p
              className="text-xl text-gray-400 max-w-2xl mx-auto"
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8, delay: 0.4 }}
              viewport={{ once: true }}
            >
              Every feature designed with security, simplicity, and maximum returns in mind
            </motion.p>
          </motion.div>

          <motion.div
            className="grid md:grid-cols-3 gap-8"
            variants={staggerContainer}
            initial="initial"
            whileInView="animate"
            viewport={{ once: true }}
          >
            {[
              {
                icon: TrendingUp,
                title: "Optimized Yields",
                description:
                  "Advanced algorithms automatically compound your rewards and optimize staking strategies for maximum returns.",
                color: "from-blue-500 to-cyan-400",
              },
              {
                icon: Shield,
                title: "Bank-Grade Security",
                description:
                  "Multi-signature wallets, insurance coverage, and institutional-grade security protocols protect your assets.",
                color: "from-cyan-400 to-blue-500",
              },
              {
                icon: Zap,
                title: "Instant Access",
                description:
                  "Connect any Solana wallet and start earning immediately. No complex setup or lengthy verification processes.",
                color: "from-blue-600 to-blue-400",
              },
            ].map((feature, index) => (
              <motion.div
                key={feature.title}
                variants={fadeInUp}
                initial={{ opacity: 0, y: 50 }}
                whileInView={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.8, delay: index * 0.2 }}
                viewport={{ once: true }}
              >
                <InteractiveCard className="p-8 h-full">
                  <div className="space-y-6">
                    <motion.div
                      className={`w-14 h-14 bg-gradient-to-br ${feature.color} rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300 relative overflow-hidden`}
                      whileHover={{ rotate: 360 }}
                      transition={{ duration: 0.8 }}
                    >
                      <motion.div className="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                      <feature.icon className="h-7 w-7 text-white relative z-10" />
                    </motion.div>
                    <div>
                      <motion.h3
                        className="text-xl font-semibold text-white mb-3 group-hover:text-blue-400 transition-colors duration-300"
                        whileHover={{ x: 5 }}
                      >
                        {feature.title}
                      </motion.h3>
                      <motion.p
                        className="text-gray-400 leading-relaxed"
                        initial={{ opacity: 0.8 }}
                        whileHover={{ opacity: 1 }}
                      >
                        {feature.description}
                      </motion.p>
                    </div>
                  </div>
                </InteractiveCard>
              </motion.div>
            ))}
          </motion.div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="px-6 py-20 relative">
        <div className="max-w-4xl mx-auto text-center">
          <motion.div
            className="bg-gradient-to-r from-blue-900/20 to-cyan-900/20 border border-blue-500/20 rounded-3xl p-12 backdrop-blur-sm relative overflow-hidden group"
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
            viewport={{ once: true }}
            whileHover={{ scale: 1.02 }}
          >
            <motion.div className="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-cyan-400/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
            <motion.h2
              className="text-4xl lg:text-5xl font-bold mb-6 relative z-10"
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8, delay: 0.2 }}
              viewport={{ once: true }}
            >
              Ready to{" "}
              <motion.span
                className="bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent"
                whileHover={{ scale: 1.05 }}
                transition={{ type: "spring", stiffness: 300, damping: 20 }}
              >
                maximize
              </motion.span>{" "}
              your returns?
            </motion.h2>
            <motion.p
              className="text-xl text-gray-400 mb-8 max-w-2xl mx-auto relative z-10"
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8, delay: 0.4 }}
              viewport={{ once: true }}
            >
              Join thousands of investors already earning with Vault's advanced staking platform
            </motion.p>
            <Link href="/signup">
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.8, delay: 0.6 }}
                viewport={{ once: true }}
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
              >
                <Button
                  size="lg"
                  className="bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white px-12 py-4 text-lg font-semibold rounded-full shadow-2xl shadow-blue-500/25 hover:shadow-blue-500/40 transition-all duration-300 group relative overflow-hidden z-10"
                >
                  <motion.div className="absolute inset-0 bg-gradient-to-r from-cyan-400/20 to-blue-400/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                  <span className="relative z-10">Get Started Now</span>
                  <ArrowRight className="ml-2 h-5 w-5 group-hover:translate-x-1 transition-transform duration-200 relative z-10" />
                </Button>
              </motion.div>
            </Link>
          </motion.div>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-gray-800/50 px-6 py-12 relative">
        <motion.div
          className="max-w-7xl mx-auto"
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8 }}
          viewport={{ once: true }}
        >
          <div className="flex flex-col md:flex-row justify-between items-center">
            <motion.div className="flex items-center space-x-3 mb-4 md:mb-0" whileHover={{ scale: 1.05 }}>
              <VaultLogo
                width={180}
                height={50}
                className="h-12 w-auto opacity-80 hover:opacity-100 transition-opacity duration-300"
              />
            </motion.div>
            <motion.div className="text-gray-500 text-sm" whileHover={{ color: "#9CA3AF" }}>
              © 2025 Vault. All rights reserved.
            </motion.div>
          </div>
        </motion.div>
      </footer>
    </div>
  )
}
