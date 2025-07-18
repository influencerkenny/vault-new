"use client"

import type React from "react"

import { useState } from "react"
import { motion } from "framer-motion"
import {
  CheckCircle,
  Clock,
  Zap,
  Shield,
  Rocket,
  Users,
  Globe,
  Cpu,
  CreditCard,
  TrendingUp,
  Building,
  Coins,
} from "lucide-react"
import { Card } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"

interface RoadmapItem {
  id: string
  title: string
  description: string
  status: "completed" | "in-progress" | "upcoming"
  year: string
  quarter: string
  features: string[]
  technicalSpecs: string[]
  icon: React.ReactNode
  marketImpact: string
}

const roadmapData: RoadmapItem[] = [
  {
    id: "1",
    title: "DeFi Foundation & Core Infrastructure",
    description: "Establishing enterprise-grade staking infrastructure with institutional security standards",
    status: "completed",
    year: "2024",
    quarter: "Q1-Q2",
    features: [
      "Multi-signature smart contracts with timelock governance",
      "Automated Market Maker (AMM) integration",
      "Yield farming protocols with dynamic APY optimization",
      "Liquidity mining rewards distribution",
      "Cross-collateral lending mechanisms",
      "Flash loan protection and MEV resistance",
    ],
    technicalSpecs: [
      "EIP-4626 tokenized vault standard implementation",
      "Chainlink oracle integration for price feeds",
      "OpenZeppelin security framework",
      "Gas optimization with CREATE2 deployment",
      "Layer 2 scaling with Polygon and Arbitrum",
    ],
    icon: <Shield className="w-6 h-6" />,
    marketImpact: "Foundation for $10M+ TVL capacity",
  },
  {
    id: "2",
    title: "Advanced Portfolio Management Suite",
    description: "Institutional-grade portfolio tools with risk management and automated rebalancing",
    status: "in-progress",
    year: "2024",
    quarter: "Q3-Q4",
    features: [
      "Delta-neutral strategies with perpetual futures",
      "Impermanent loss protection mechanisms",
      "Automated portfolio rebalancing with Markowitz optimization",
      "Risk-adjusted yield farming with VaR calculations",
      "Leveraged staking with liquidation protection",
      "Cross-protocol yield aggregation",
    ],
    technicalSpecs: [
      "Integration with Aave, Compound, and Yearn protocols",
      "Uniswap V4 hooks for custom liquidity strategies",
      "Gelato Network automation for rebalancing",
      "1inch API for optimal swap routing",
      "Tenderly simulation for strategy backtesting",
    ],
    icon: <TrendingUp className="w-6 h-6" />,
    marketImpact: "Target $100M+ TVL with institutional adoption",
  },
  {
    id: "3",
    title: "Cross-Chain Interoperability & Institutional Access",
    description: "Multi-chain ecosystem with institutional custody and compliance features",
    status: "upcoming",
    year: "2025",
    quarter: "Q1-Q3",
    features: [
      "Cross-chain bridge aggregation with LayerZero",
      "Institutional custody with Fireblocks integration",
      "Regulatory compliance with KYC/AML frameworks",
      "Tax reporting and capital gains optimization",
      "Prime brokerage services for large accounts",
      "OTC trading desk with deep liquidity access",
    ],
    technicalSpecs: [
      "Wormhole and Axelar bridge integrations",
      "Cosmos IBC protocol support",
      "EIP-3074 account abstraction implementation",
      "Zero-knowledge proofs for privacy compliance",
      "Multi-party computation (MPC) wallet architecture",
    ],
    icon: <Globe className="w-6 h-6" />,
    marketImpact: "$500M+ TVL target with institutional clients",
  },
  {
    id: "4",
    title: "Vault Card & Real-World Asset Integration",
    description: "Physical debit card linked to DeFi yields with RWA tokenization platform",
    status: "upcoming",
    year: "2026",
    quarter: "Q1-Q2",
    features: [
      "Vault Card: Visa/Mastercard debit card with real-time DeFi spending",
      "Real-world asset tokenization (real estate, commodities)",
      "Fractional ownership of institutional-grade assets",
      "Automated yield-to-spending conversion",
      "Global ATM network with fee reimbursements",
      "Merchant rewards program with cashback in native tokens",
    ],
    technicalSpecs: [
      "ISO 20022 compliance for traditional banking integration",
      "Chainlink CCIP for cross-chain card settlements",
      "Account abstraction with social recovery",
      "Real-time settlement with stablecoin rails",
      "Regulatory sandbox partnerships in key jurisdictions",
    ],
    icon: <CreditCard className="w-6 h-6" />,
    marketImpact: "Revolutionary DeFi-to-fiat bridge, targeting 100K+ cardholders",
  },
  {
    id: "5",
    title: "AI-Powered Investment Intelligence",
    description: "Machine learning algorithms for predictive analytics and automated strategy optimization",
    status: "upcoming",
    year: "2026",
    quarter: "Q3-Q4",
    features: [
      "AI-driven yield prediction with sentiment analysis",
      "Automated strategy optimization using reinforcement learning",
      "Risk assessment with real-time market correlation analysis",
      "Personalized investment recommendations",
      "Predictive liquidation warnings and auto-hedging",
      "Market making algorithms for improved liquidity",
    ],
    technicalSpecs: [
      "TensorFlow integration for on-chain ML inference",
      "Chainlink Functions for off-chain AI computation",
      "Graph neural networks for DeFi protocol analysis",
      "Federated learning for privacy-preserving model training",
      "Real-time data streaming with Apache Kafka",
    ],
    icon: <Cpu className="w-6 h-6" />,
    marketImpact: "AI-first DeFi platform with predictive capabilities",
  },
  {
    id: "6",
    title: "Decentralized Autonomous Organization (DAO)",
    description: "Community governance with advanced voting mechanisms and treasury management",
    status: "upcoming",
    year: "2027",
    quarter: "Q1-Q2",
    features: [
      "Quadratic voting for fair governance participation",
      "Conviction voting for long-term decision making",
      "Automated treasury management with yield optimization",
      "Proposal execution with multi-sig security",
      "Reputation-based voting weights",
      "Cross-chain governance with unified token standards",
    ],
    technicalSpecs: [
      "Governor Bravo with custom voting strategies",
      "Snapshot off-chain voting with on-chain execution",
      "Gnosis Safe multi-sig treasury management",
      "Aragon DAO framework integration",
      "Token-weighted governance with delegation",
    ],
    icon: <Users className="w-6 h-6" />,
    marketImpact: "Fully decentralized protocol with community ownership",
  },
  {
    id: "7",
    title: "Institutional DeFi Infrastructure",
    description: "Enterprise-grade solutions for banks, hedge funds, and asset managers",
    status: "upcoming",
    year: "2027",
    quarter: "Q3-Q4",
    features: [
      "White-label DeFi solutions for traditional finance",
      "Institutional-grade API with SLA guarantees",
      "Regulatory reporting automation",
      "Custom smart contract deployment platform",
      "High-frequency trading infrastructure",
      "Institutional staking-as-a-service",
    ],
    technicalSpecs: [
      "GraphQL APIs with rate limiting and authentication",
      "Kubernetes orchestration for scalable infrastructure",
      "Redis caching for sub-second response times",
      "PostgreSQL with read replicas for data analytics",
      "Prometheus monitoring with custom alerting",
    ],
    icon: <Building className="w-6 h-6" />,
    marketImpact: "B2B revenue streams with enterprise clients",
  },
  {
    id: "8",
    title: "Global Financial Ecosystem",
    description: "Complete DeFi ecosystem with traditional finance integration and global expansion",
    status: "upcoming",
    year: "2028-2029",
    quarter: "Q1-Q4",
    features: [
      "Central bank digital currency (CBDC) integration",
      "Traditional banking partnerships and API integrations",
      "Global remittance network with instant settlements",
      "Decentralized identity and credit scoring",
      "Insurance protocols for smart contract coverage",
      "Carbon credit trading and ESG compliance tools",
    ],
    technicalSpecs: [
      "ISO 20022 messaging for SWIFT network integration",
      "W3C Decentralized Identity standards",
      "Zero-knowledge identity verification",
      "Interledger Protocol (ILP) for cross-border payments",
      "Hyperledger Fabric for enterprise blockchain needs",
    ],
    icon: <Coins className="w-6 h-6" />,
    marketImpact: "Global DeFi infrastructure serving millions of users",
  },
]

const statusConfig = {
  completed: {
    color: "bg-green-500/20 text-green-400 border-green-500/30",
    icon: <CheckCircle className="w-4 h-4" />,
    label: "Completed",
  },
  "in-progress": {
    color: "bg-blue-500/20 text-blue-400 border-blue-500/30",
    icon: <Clock className="w-4 h-4" />,
    label: "In Progress",
  },
  upcoming: {
    color: "bg-purple-500/20 text-purple-400 border-purple-500/30",
    icon: <Rocket className="w-4 h-4" />,
    label: "Upcoming",
  },
}

export function RoadmapTimeline() {
  const [selectedItem, setSelectedItem] = useState<string | null>(null)

  return (
    <div className="relative">
      {/* Timeline line */}
      <div className="absolute left-8 top-0 bottom-0 w-0.5 bg-gradient-to-b from-blue-500/50 via-purple-500/50 to-pink-500/50" />

      <div className="space-y-12">
        {roadmapData.map((item, index) => (
          <motion.div
            key={item.id}
            initial={{ opacity: 0, x: -50 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: index * 0.1 }}
            className="relative"
          >
            {/* Timeline dot */}
            <div className="absolute left-6 w-4 h-4 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 border-2 border-background z-10" />

            <Card
              className={`ml-16 glass glow-on-hover cursor-pointer transition-all duration-300 ${
                item.id === "4" ? "ring-2 ring-yellow-500/50 shadow-yellow-500/20 shadow-lg" : ""
              }`}
              onClick={() => setSelectedItem(selectedItem === item.id ? null : item.id)}
            >
              <div className="p-6">
                <div className="flex items-start justify-between mb-4">
                  <div className="flex items-center gap-3">
                    <div className="p-2 rounded-lg bg-blue-500/20">{item.icon}</div>
                    <div>
                      <div className="flex items-center gap-2 mb-1">
                        <h3 className="text-xl font-bold text-white">{item.title}</h3>
                        {item.id === "4" && (
                          <Badge className="bg-yellow-500/20 text-yellow-400 border-yellow-500/30 text-xs">
                            🚀 VAULT CARD
                          </Badge>
                        )}
                      </div>
                      <p className="text-gray-400 text-sm">
                        {item.year} • {item.quarter}
                      </p>
                    </div>
                  </div>

                  <Badge className={statusConfig[item.status].color}>
                    {statusConfig[item.status].icon}
                    <span className="ml-1">{statusConfig[item.status].label}</span>
                  </Badge>
                </div>

                <p className="text-gray-300 mb-3">{item.description}</p>

                <div className="text-sm text-blue-400 font-medium mb-4">📈 {item.marketImpact}</div>

                <motion.div
                  initial={false}
                  animate={{ height: selectedItem === item.id ? "auto" : 0 }}
                  className="overflow-hidden"
                >
                  <div className="pt-4 border-t border-white/10 space-y-4">
                    <div>
                      <h4 className="text-sm font-semibold text-white mb-3 flex items-center gap-2">
                        <Zap className="w-4 h-4 text-blue-400" />
                        Key Features:
                      </h4>
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                        {item.features.map((feature, featureIndex) => (
                          <motion.div
                            key={featureIndex}
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: featureIndex * 0.05 }}
                            className="flex items-start gap-2 text-sm text-gray-300"
                          >
                            <div className="w-1.5 h-1.5 rounded-full bg-blue-400 mt-2 flex-shrink-0" />
                            {feature}
                          </motion.div>
                        ))}
                      </div>
                    </div>

                    <div>
                      <h4 className="text-sm font-semibold text-white mb-3 flex items-center gap-2">
                        <Cpu className="w-4 h-4 text-purple-400" />
                        Technical Specifications:
                      </h4>
                      <div className="grid grid-cols-1 gap-2">
                        {item.technicalSpecs.map((spec, specIndex) => (
                          <motion.div
                            key={specIndex}
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: specIndex * 0.05 }}
                            className="flex items-start gap-2 text-sm text-gray-400 font-mono"
                          >
                            <div className="w-1.5 h-1.5 rounded-full bg-purple-400 mt-2 flex-shrink-0" />
                            {spec}
                          </motion.div>
                        ))}
                      </div>
                    </div>
                  </div>
                </motion.div>
              </div>
            </Card>
          </motion.div>
        ))}
      </div>
    </div>
  )
}
