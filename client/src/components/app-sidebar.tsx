import { 
  ChartBar, 
  TrendingUp, 
  Briefcase, 
  Building, 
  Stethoscope, 
  DollarSign,
  Users,
  Settings,
  LogOut,
} from "lucide-react";
import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarFooter,
} from "@/components/ui/sidebar";
import { Link, useLocation } from "wouter";
import { useAuth } from "@/hooks/useAuth";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";

const menuItems = [
  {
    title: "Dashboard",
    url: "/",
    icon: TrendingUp,
  },
  {
    title: "Relatórios",
    url: "/relatorios",
    icon: ChartBar,
  },
];

const auxiliaryTables = [
  {
    title: "CBO (Ocupações)",
    url: "/cbo",
    icon: Briefcase,
  },
  {
    title: "Prestadores",
    url: "/prestador",
    icon: Building,
  },
  {
    title: "Procedimentos",
    url: "/procedimento",
    icon: Stethoscope,
  },
  {
    title: "Financiamentos",
    url: "/srub",
    icon: DollarSign,
  },
];

const systemItems = [
  {
    title: "Usuários",
    url: "/usuarios",
    icon: Users,
    adminOnly: true,
  },
  {
    title: "Configurações",
    url: "/config",
    icon: Settings,
    adminOnly: true,
  },
];

export function AppSidebar() {
  const [location] = useLocation();
  const { user, logout } = useAuth();

  const isActiveRoute = (url: string) => {
    return location === url;
  };

  const getUserInitials = () => {
    if (user?.firstName && user?.lastName) {
      return `${user.firstName[0]}${user.lastName[0]}`.toUpperCase();
    }
    return user?.username?.[0]?.toUpperCase() || "U";
  };

  const getUserDisplayName = () => {
    if (user?.firstName && user?.lastName) {
      return `${user.firstName} ${user.lastName}`;
    }
    return user?.username || "Usuário";
  };

  const getRoleDisplayName = () => {
    switch (user?.role) {
      case 'admin':
        return 'Administrador';
      case 'operator':
        return 'Operador';
      default:
        return 'Usuário';
    }
  };

  return (
    <Sidebar data-testid="sidebar-main">
      <SidebarContent>
        {/* Header */}
        <div className="p-6 border-b border-sidebar-border">
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
              <TrendingUp className="text-primary-foreground h-5 w-5" />
            </div>
            <div>
              <h1 className="text-lg font-semibold text-sidebar-foreground">ConsultaProd</h1>
              <p className="text-sm text-muted-foreground">Sistema de Gestão</p>
            </div>
          </div>
        </div>

        {/* Main Navigation */}
        <SidebarGroup>
          <SidebarGroupContent>
            <SidebarMenu>
              {menuItems.map((item) => (
                <SidebarMenuItem key={item.title}>
                  <SidebarMenuButton asChild isActive={isActiveRoute(item.url)}>
                    <Link href={item.url} data-testid={`nav-${item.title.toLowerCase()}`}>
                      <item.icon className="h-4 w-4" />
                      <span>{item.title}</span>
                    </Link>
                  </SidebarMenuButton>
                </SidebarMenuItem>
              ))}
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>

        {/* Auxiliary Tables */}
        <SidebarGroup>
          <SidebarGroupLabel>Tabelas Auxiliares</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu>
              {auxiliaryTables.map((item) => (
                <SidebarMenuItem key={item.title}>
                  <SidebarMenuButton asChild isActive={isActiveRoute(item.url)}>
                    <Link href={item.url} data-testid={`nav-${item.title.toLowerCase().replace(/\s+/g, '-')}`}>
                      <item.icon className="h-4 w-4" />
                      <span>{item.title}</span>
                    </Link>
                  </SidebarMenuButton>
                </SidebarMenuItem>
              ))}
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>

        {/* System - Only for Admin */}
        {user?.role === 'admin' && (
          <SidebarGroup>
            <SidebarGroupLabel>Sistema</SidebarGroupLabel>
            <SidebarGroupContent>
              <SidebarMenu>
                {systemItems.map((item) => (
                  <SidebarMenuItem key={item.title}>
                    <SidebarMenuButton asChild isActive={isActiveRoute(item.url)}>
                      <Link href={item.url} data-testid={`nav-${item.title.toLowerCase()}`}>
                        <item.icon className="h-4 w-4" />
                        <span>{item.title}</span>
                      </Link>
                    </SidebarMenuButton>
                  </SidebarMenuItem>
                ))}
              </SidebarMenu>
            </SidebarGroupContent>
          </SidebarGroup>
        )}
      </SidebarContent>

      {/* Footer */}
      <SidebarFooter>
        <div className="p-4 border-t border-sidebar-border">
          <div className="flex items-center space-x-3">
            <Avatar className="h-8 w-8">
              <AvatarImage src="" />
              <AvatarFallback className="bg-secondary text-secondary-foreground">
                {getUserInitials()}
              </AvatarFallback>
            </Avatar>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-sidebar-foreground truncate">
                {getUserDisplayName()}
              </p>
              <p className="text-xs text-muted-foreground">{getRoleDisplayName()}</p>
            </div>
            <Button
              variant="ghost"
              size="icon"
              onClick={logout}
              className="h-8 w-8"
              data-testid="button-logout"
            >
              <LogOut className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </SidebarFooter>
    </Sidebar>
  );
}
