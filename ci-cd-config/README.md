# CI/CD Pipeline Setup Instructions

This directory contains the GitHub Actions CI/CD pipeline configuration for the Leave Manager plugin.

## Prerequisites

1. A GitHub repository with the Leave Manager plugin code
2. A GitHub Personal Access Token (PAT) with `workflow` scope

## Setup Steps

### Step 1: Generate a Personal Access Token

1. Go to GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Click "Generate new token (classic)"
3. Give it a descriptive name (e.g., "Leave Manager CI/CD")
4. Select the following scopes:
   - `repo` (Full control of private repositories)
   - `workflow` (Update GitHub Action workflows)
5. Click "Generate token"
6. **Copy the token immediately** (you won't be able to see it again)

### Step 2: Add the Workflow File

1. In your repository, create the directory structure:
   ```
   .github/workflows/
   ```

2. Copy the `ci.yml` file from this directory to `.github/workflows/ci.yml`

3. Commit and push the changes:
   ```bash
   git add .github/workflows/ci.yml
   git commit -m "Add GitHub Actions CI/CD pipeline"
   git push origin main
   ```

### Step 3: Verify the Pipeline

1. Go to your repository on GitHub
2. Click on the "Actions" tab
3. You should see the CI workflow running

## Pipeline Features

### Test Job
- Runs PHPUnit tests across multiple PHP versions (8.0, 8.1, 8.2)
- Tests against multiple WordPress versions (6.4, 6.5, latest)
- Uses MySQL 8.0 for database testing
- Uploads code coverage reports to Codecov

### Lint Job
- Checks for PHP syntax errors
- Runs PHP CodeSniffer with PSR-12 standard

### Security Job
- Runs Composer security audit
- Checks for known vulnerabilities in dependencies

### Deploy Jobs
- **Staging**: Automatically deploys to staging when pushing to `develop` branch
- **Production**: Creates release artifact when pushing to `main` branch

## Customization

### Adding Deployment Scripts

Edit the `deploy-staging` and `deploy-production` jobs in `ci.yml` to add your deployment scripts:

```yaml
- name: Deploy to staging
  run: |
    # Add your deployment commands here
    scp -r . user@staging-server:/var/www/html/wp-content/plugins/leave-manager/
```

### Adding Secrets

For deployment, you may need to add secrets to your repository:

1. Go to Repository → Settings → Secrets and variables → Actions
2. Click "New repository secret"
3. Add secrets like:
   - `STAGING_SERVER_HOST`
   - `STAGING_SERVER_USER`
   - `STAGING_SSH_KEY`
   - `PRODUCTION_SERVER_HOST`
   - etc.

## Troubleshooting

### Pipeline fails with "workflow scope" error

This means your PAT doesn't have the `workflow` scope. Generate a new token with the correct scope.

### Tests fail in CI but pass locally

This is usually due to environment differences. Check:
- PHP version compatibility
- Database configuration
- WordPress version compatibility

### Deployment fails

Check that:
- All required secrets are configured
- Server credentials are correct
- SSH keys have proper permissions
