import { useAuth } from './stores/AuthProvider';

// Simple working components
const LoginPage = () => (
  <div className="min-h-screen bg-gray-50 flex items-center justify-center">
    <div className="text-center">
      <h1 className="text-4xl font-bold text-gray-900 mb-4">Login</h1>
      <p className="text-xl text-gray-600">Login page placeholder</p>
    </div>
  </div>
);

const Dashboard = ({ title, userType }: { title: string; userType: string }) => (
  <div className="min-h-screen bg-gray-50">
    {/* Header */}
    <header className="bg-white shadow-sm border-b border-gray-200">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          <div className="flex items-center">
            <h1 className="text-xl font-semibold text-gray-900">{title}</h1>
          </div>
          <div className="flex items-center space-x-4">
            <button className="text-sm text-gray-500 hover:text-gray-700">
              Logout
            </button>
          </div>
        </div>
      </div>
    </header>

    {/* Navigation */}
    <nav className="bg-white shadow-sm border-b border-gray-200">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex space-x-8 py-4">
          <a href="#" className="px-3 py-2 rounded-md text-sm font-medium bg-blue-100 text-blue-700">
            Dashboard
          </a>
          <a href="#" className="px-3 py-2 rounded-md text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50">
            Workers
          </a>
          <a href="#" className="px-3 py-2 rounded-md text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50">
            Contracts
          </a>
        </div>
      </div>
    </nav>

    {/* Main Content */}
    <main className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div className="text-center">
        <h2 className="text-3xl font-bold text-gray-900 mb-4">Welcome to {userType} Portal</h2>
        <p className="text-xl text-gray-600">This is a working dashboard with navigation!</p>
        <div className="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="bg-white p-6 rounded-lg shadow">
            <h3 className="text-lg font-medium text-gray-900 mb-2">Quick Stats</h3>
            <p className="text-gray-600">View your important metrics</p>
          </div>
          <div className="bg-white p-6 rounded-lg shadow">
            <h3 className="text-lg font-medium text-gray-900 mb-2">Recent Activity</h3>
            <p className="text-gray-600">See what's happening</p>
          </div>
          <div className="bg-white p-6 rounded-lg shadow">
            <h3 className="text-lg font-medium text-gray-900 mb-2">Actions</h3>
            <p className="text-gray-600">Quick actions you can take</p>
          </div>
        </div>
      </div>
    </main>
  </div>
);

function App() {
  const { user, isAuthenticated } = useAuth();

  if (!isAuthenticated) {
    return <LoginPage />;
  }

  // Determine user type and show appropriate dashboard
  const userType = user?.roles?.some((role: any) => ['admin', 'internal'].includes(role.name))
    ? 'Admin'
    : user?.roles?.some((role: any) => role.name === 'agency')
    ? 'Agency'
    : 'Customer';

  const title = `${userType} Dashboard`;

  return <Dashboard title={title} userType={userType} />;
}

export default App;
