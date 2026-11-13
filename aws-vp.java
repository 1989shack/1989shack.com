module "vpc" {
  source   = "aws-ia/vpc/aws"
  version = ">= 4.2.0"

  name                                 = "multi-az-vpc"
  cidr_block                           = "10.0.0.0/16"
  vpc_assign_generated_ipv6_cidr_block = true
  vpc_egress_only_internet_gateway     = true
  az_count                             = 3

  subnets = {
    # Dual-stack subnet
    public = {
      name_prefix               = "my_public" # omit to prefix with "public"
      netmask                   = 24
      assign_ipv6_cidr          = true
      nat_gateway_configuration = "all_azs" # options: "single_az", "none"
    }
    # IPv4 only subnet
    private = {
      # omitting name_prefix defaults value to "private"
      # name_prefix  = "private_with_egress"
      netmask      = 24
      connect_to_public_natgw = true
    }
    # IPv6-only subnet
    private_ipv6 = {
      ipv6_native      = true
      assign_ipv6_cidr = true
      connect_to_eigw  = true
    }
  }

  vpc_flow_logs = {
    log_destination_type = "cloud-watch-logs"
    retention_in_days    = 180
  }
}
