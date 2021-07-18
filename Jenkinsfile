pipeline {
  agent none
  environment {
    GITHUB = credentials('github')
    SONAR_HOST_URL = credentials('sonar-url')
    SONAR_LOGIN = credentials('sonar-token')
  }
  stages {
    stage("Build") {
      agent {
        kubernetes {
          inheritFrom "composer sonar"
        }
      }
      steps {
        container("composer") {
          sh "composer install"
          sh "./vendor/bin/phpunit"
        }
        post {
          always {
            container('sonar') {
              sh('sonar-scanner -Dsonar.login=$SONAR_LOGIN')
            }
          }
          success {
            stash name: env.BUILD_TAG, includes: "**"
          }
        }
      }
    }
    stage("Build Tag") {
      agent {
        kubernetes {
          yaml '''
spec:
    containers:
    - name: python
      image: python:3.9-alpine
'''
        }
      }
      when {
        not {
          buildTag()
        }
        changelog '.*^bump version$'
      }
      steps {
        sh "git config user.email \"jenkins-bot@mgufron.com\""
        sh "git config user.name \"jenkins.bot\""
        script {
          def releaseType = "patch"
          container("python") {
            sh(script: "pip install gitchangelog")
            def lastTag = sh(script: "git describe --abbrev=0 --tags", returnStdout: true)
            def versions = lastTag.split(".")
            def notes = sh(script: "gitchangelog $lastTag..", returnStdout: true)
            if (notes =~ /(?im)change(s?)\n\~/) {
              releaseType = "minor"
            }
            if (notes =~ /(?im)new\n\~/) {
              releaseType = "major"
            }
            switch (releaseType) {
              case "major":
                versions[0] += 1
                break
              case "minor":
                versions[1] += 1
                break
              case "patch":
                versions[2] += 1
                break
            }
            def currentVersion = versions.join(".")
            sh "git tag $currentVersion"
            sh "git push origin $currentVersion"
            build job: "php-bumper", parameters: [string(name: "VERSION", value: currentVersion), string(name: "PACKAGE", value: "mgufrone/healthcheck-bundle"), string(name: "BRANCH", value: "main")]
          }
        }
      }
    }
  }
}
